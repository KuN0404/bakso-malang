<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\CartPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Services\CartValidationService;
use App\Services\ComponentStockService;
use App\Services\ReportSyncService;
use Illuminate\Support\Facades\DB;

/**
 * Memproses pembayaran Cash secara atomik dalam satu DB transaction.
 */
class InitiateCashPaymentAction
{
    public function __construct(
        private readonly ReportSyncService $reportSyncService,
        private readonly ComponentStockService $componentStockService,
        private readonly CartValidationService $cartValidationService,
    ) {}

    /**
     * @throws \Exception
     */
    public function execute(CartPayload $payload, int $cashierId): Transaction
    {
        return DB::transaction(function () use ($payload, $cashierId) {
            // -- Hitung ulang harga dari DB — JANGAN percaya unit_price/subtotal dari client --
            $validated = $this->cartValidationService->validateAndPrice($payload->cart);
            $items     = $validated['items'];
            $subtotal  = $validated['subtotal'];

            $taxRate   = (float) Setting::get('tax_percentage', 0, 'general');
            $taxAmount = round($subtotal * ($taxRate / 100), 2);
            $total     = $subtotal + $taxAmount;

            $paymentSource = PaymentSource::findOrFail($payload->paymentSourceId);
            $isCash        = $paymentSource->type === 'cash';

            $paidAmount = $isCash ? $payload->paidAmount : $total;

            if ($isCash && $paidAmount < $total) {
                throw new \RuntimeException('Jumlah pembayaran kurang dari total transaksi.');
            }

            $changeAmount = $isCash ? max(0, $paidAmount - $total) : 0;

            // -- Validasi stok dengan reservation awareness (Opsi A: sinkron POS ↔ Self Order) --
            $productQuantities = $this->aggregateQuantities($items);
            $productIds        = array_keys($productQuantities);
            $products          = Product::getForCheckout($productIds)->keyBy('id');

            foreach ($productQuantities as $productId => $totalQty) {
                $product = $products->get($productId);
                if ($product && $product->track_stock) {
                    // Gunakan available stock (stock fisik - active reservations Self Order)
                    $availableStock = $product->getAvailableStock();
                    if ($totalQty > $availableStock) {
                        throw new \RuntimeException(
                            "Stok {$product->name} tidak cukup. " .
                            "Tersedia: {$availableStock} (Fisik: {$product->stock}, Reserved: " .
                            ($product->stock - $availableStock) . "), Dibutuhkan: {$totalQty}."
                        );
                    }
                }
            }

            $shift         = Shift::getOrCreateTodayShift($cashierId);
            $queueNumber   = DailyQueueNumber::getNextNumber();
            $invoiceNumber = Transaction::generateInvoiceNumber();

            // -- Buat PaymentTransaction (status paid langsung untuk pembayaran manual/cash) --
            $paymentTx = PaymentTransaction::create([
                'invoice_number'   => $invoiceNumber,
                'midtrans_order_id'=> strtoupper($paymentSource->type) . '-' . $invoiceNumber,
                'payment_method'   => $paymentSource->type,
                'amount'           => $total,
                'status'           => PaymentTransactionStatus::Paid->value,
                'paid_at'          => now(),
                'created_by'       => $cashierId,
            ]);

            $paymentTx->statusLogs()->create([
                'from_status'  => null,
                'to_status'    => PaymentTransactionStatus::Paid->value,
                'triggered_by' => 'cashier',
                'actor_id'     => $cashierId,
                'note'         => "Pembayaran {$paymentSource->name} langsung",
            ]);

            // -- Buat Transaction POS --
            $transaction = Transaction::create([
                'user_id'                => $cashierId,
                'shift_id'               => $shift->id,
                'payment_source_id'      => $paymentSource->id,
                'payment_transaction_id' => $paymentTx->id,
                'service_area_id'        => $payload->orderType === 'dine_in' ? $payload->serviceAreaId : null,
                'invoice_number'         => $invoiceNumber,
                'queue_number'           => $queueNumber,
                'subtotal'               => $subtotal,
                'discount_amount'        => 0,
                'tax_amount'             => $taxAmount,
                'total'                  => $total,
                'paid_amount'            => $paidAmount,
                'change_amount'          => $changeAmount,
                'payment_method'         => $paymentSource->type,
                'payment_gateway_status' => null,
                'status'                 => 'completed',
                'customer_name'          => $payload->customerName ?: null,
                'order_type'             => $payload->orderType,
                'notes'                  => $payload->notes ?: null,
            ]);

            // Update payment_tx dengan transaction_id
            $paymentTx->update(['transaction_id' => $transaction->id]);

            // -- Detail + Stok (pakai $items yang sudah divalidasi ulang dari DB) --
            $this->createDetailsAndDeductStock($transaction, $items, $products, $cashierId);

            $transaction->load(['details.modifiers', 'user', 'paymentSource', 'serviceArea']);

            // Sync laporan (di luar lock tapi masih di dalam DB transaction)
            $this->reportSyncService->syncTransaction($transaction);

            return $transaction;
        });
    }

    private function aggregateQuantities(array $cart): array
    {
        $quantities = [];
        foreach ($cart as $item) {
            $pid = $item['product_id'];
            $quantities[$pid] = ($quantities[$pid] ?? 0) + $item['quantity'];
        }
        return $quantities;
    }

    private function createDetailsAndDeductStock(
        Transaction $transaction,
        array $cart,
        \Illuminate\Support\Collection $products,
        int $cashierId
    ): void {
        foreach ($cart as $item) {
            $detail = $transaction->details()->create([
                'product_id'     => $item['product_id'],
                'product_name'   => $item['product_name'],
                'unit_price'     => $item['unit_price'],
                'quantity'       => $item['quantity'],
                'modifier_total' => $item['modifier_total'],
                'subtotal'       => $item['subtotal'],
            ]);

            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifierId => $modifierData) {
                    $modQty = (int) ($modifierData['qty'] ?? 1);
                    $detail->modifiers()->attach($modifierId, [
                        'modifier_name'    => $modifierData['name'],
                        'price_adjustment' => $modifierData['price'],
                        'quantity'         => $modQty,
                    ]);

                    // Kurangi stok komponen modifier jika modifier terhubung ke komponen
                    if (!empty($modifierData['component_id'])) {
                        $totalModQty = $modQty * $item['quantity'];
                        $this->componentStockService->deductForModifier(
                            $modifierId,
                            $totalModQty,
                            $transaction->id,
                            $cashierId
                        );
                    }
                }
            }

            $product = $products->get($item['product_id']);

            if ($product && $product->hasBom()) {
                // Mode BOM: Kurangi stok komponen yang terdaftar pada BOM produk ini
                $this->componentStockService->deductForBom(
                    $item['product_id'],
                    $item['quantity'],
                    $transaction->id,
                    $cashierId
                );
            } elseif ($product && $product->track_stock) {
                // Mode Direct Stock: Kurangi stok produk (backward compat)
                $product->decrement('stock', $item['quantity']);
                StockLog::record(
                    productId:  $product->id,
                    userId:     $cashierId,
                    type:       'sale',
                    amount:     -$item['quantity'],
                    finalStock: $product->fresh()->stock,
                    note:       "Inv: {$transaction->invoice_number}"
                );
            }
        }
    }
}
