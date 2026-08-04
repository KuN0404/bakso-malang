<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Shift;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Services\ComponentStockService;
use App\Services\ReportSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Memproses webhook Midtrans secara aman dengan idempotency dan row-level locking.
 *
 * Flow (untuk settlement/capture):
 *  1. Lock PaymentTransaction row (lockForUpdate)
 *  2. Cek idempotency — skip jika sudah paid
 *  3. Validasi state machine
 *  4. Update PaymentTransaction → paid
 *  5. Buat Transaction POS (completed) + Detail + Kurangi stok
 *  6. Sync ke ReportDB
 */
class HandleMidtransWebhookAction
{
    public function __construct(
        private readonly ReportSyncService $reportSyncService,
        private readonly ComponentStockService $componentStockService,
    ) {}

    /**
     * @return Transaction|null  Null jika diabaikan (idempotent)
     * @throws \Exception
     */
    public function execute(MidtransWebhookPayload $webhookPayload): ?Transaction
    {
        return DB::transaction(function () use ($webhookPayload) {
            // -- 1. Lock PaymentTransaction (cegah race condition) --
            $paymentTx = PaymentTransaction::where('midtrans_order_id', $webhookPayload->orderId)
                ->lockForUpdate()
                ->first();

            if (!$paymentTx) {
                Log::warning("Webhook Midtrans: PaymentTransaction tidak ditemukan untuk orderId [{$webhookPayload->orderId}]");
                return null;
            }

            // -- 2. Idempotency check — jika sudah paid, abaikan --
            if ($paymentTx->isPaid()) {
                Log::info("Webhook Midtrans: PaymentTransaction [{$paymentTx->id}] sudah paid. Diabaikan (idempotent).");
                return $paymentTx->transaction;
            }

            // -- Update raw webhook response --
            $paymentTx->update([
                'midtrans_response'   => $webhookPayload->raw,
                'webhook_received_at' => now(),
            ]);

            // -- 3. Proses berdasarkan status webhook --
            if ($webhookPayload->isSuccess()) {
                return $this->handleSuccess($paymentTx, $webhookPayload);
            }

            if ($webhookPayload->isFailure()) {
                $this->handleFailure($paymentTx, $webhookPayload);
                return null;
            }

            if ($webhookPayload->isExpired()) {
                $this->handleExpiry($paymentTx);
                return null;
            }

            // Status lain (pending, dll) — log saja
            Log::info("Webhook Midtrans: status [{$webhookPayload->transactionStatus}] untuk order [{$webhookPayload->orderId}] diabaikan.");
            return null;
        });
    }

    private function handleSuccess(PaymentTransaction $paymentTx, MidtransWebhookPayload $webhookPayload): Transaction
    {
        if (!$paymentTx->status->canTransitionTo(PaymentTransactionStatus::Paid)) {
            throw new \DomainException(
                "Tidak bisa transisi PaymentTransaction [{$paymentTx->id}] dari [{$paymentTx->status->value}] ke paid."
            );
        }

        // Update PaymentTransaction → paid
        $paymentTx->update([
            'status'  => PaymentTransactionStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $paymentTx->statusLogs()->create([
            'from_status'  => PaymentTransactionStatus::Pending->value,
            'to_status'    => PaymentTransactionStatus::Paid->value,
            'triggered_by' => 'webhook',
            'note'         => "Midtrans settlement: {$webhookPayload->transactionId}",
            'metadata'     => $webhookPayload->raw,
        ]);

        // Lock produk dan buat transaksi POS
        $productQuantities = $this->aggregateCartFromPaymentTx($paymentTx);
        $transaction       = $this->createPosTransaction($paymentTx, $productQuantities);

        // Sync ke laporan
        $this->reportSyncService->syncTransaction($transaction);

        Log::info("Webhook Midtrans: PaymentTransaction [{$paymentTx->id}] berhasil. Transaksi POS [{$transaction->id}] dibuat.");

        return $transaction;
    }

    private function handleFailure(PaymentTransaction $paymentTx, MidtransWebhookPayload $webhookPayload): void
    {
        if ($paymentTx->isFinal()) {
            return;
        }

        $paymentTx->transitionTo(
            newStatus:   PaymentTransactionStatus::Failed,
            triggeredBy: 'webhook',
            note:        "Midtrans: {$webhookPayload->transactionStatus}",
            metadata:    $webhookPayload->raw,
        );

        Log::info("Webhook Midtrans: PaymentTransaction [{$paymentTx->id}] gagal ({$webhookPayload->transactionStatus}).");
    }

    private function handleExpiry(PaymentTransaction $paymentTx): void
    {
        if ($paymentTx->isFinal()) {
            return;
        }

        $paymentTx->transitionTo(
            newStatus:   PaymentTransactionStatus::Expired,
            triggeredBy: 'webhook',
            note:        'QRIS expired (notifikasi Midtrans)',
        );
    }

    /**
     * Karena data cart disimpan di Livewire state (server-side), kita perlu
     * rekonstruksi dari PaymentTransaction. Untuk QRIS, data cart disimpan
     * sementara di Cache saat initiateQris dipanggil.
     */
    private function aggregateCartFromPaymentTx(PaymentTransaction $paymentTx): array
    {
        $cacheKey = "qris_cart_{$paymentTx->midtrans_order_id}";
        $cartData = \Illuminate\Support\Facades\Cache::get($cacheKey);

        if (!$cartData) {
            throw new \RuntimeException(
                "Cart data tidak ditemukan di cache untuk order [{$paymentTx->midtrans_order_id}]. " .
                "Kemungkinan cache expired sebelum webhook diterima."
            );
        }

        return $cartData;
    }

    private function createPosTransaction(PaymentTransaction $paymentTx, array $cartData): Transaction
    {
        $cashierId     = $paymentTx->created_by;
        $paymentSource = PaymentSource::active()->where('type', 'qris')->first()
            ?? PaymentSource::active()->first();

        $shift       = Shift::getOrCreateTodayShift($cashierId);
        $queueNumber = DailyQueueNumber::getNextNumber();

        $transaction = Transaction::create([
            'user_id'                => $cashierId,
            'shift_id'               => $shift->id,
            'payment_source_id'      => $paymentSource?->id,
            'payment_transaction_id' => $paymentTx->id,
            'service_area_id'        => $cartData['service_area_id'] ?? null,
            'invoice_number'         => $paymentTx->invoice_number,
            'queue_number'           => $queueNumber,
            'subtotal'               => $cartData['subtotal'],
            'discount_amount'        => 0,
            'tax_amount'             => $cartData['tax_amount'],
            'total'                  => $cartData['total'],
            'paid_amount'            => $cartData['total'],
            'change_amount'          => 0,
            'payment_method'         => 'qris',
            'payment_gateway_status' => 'settlement',
            'status'                 => 'completed',
            'customer_name'          => $cartData['customer_name'] ?: null,
            'order_type'             => $cartData['order_type'],
            'notes'                  => $cartData['notes'] ?: null,
        ]);

        // Update payment_tx
        $paymentTx->update(['transaction_id' => $transaction->id]);

        // Buat details + kurangi stok
        $productIds  = array_unique(array_column($cartData['cart'], 'product_id'));
        $products    = Product::getForCheckout($productIds)->keyBy('id');

        foreach ($cartData['cart'] as $item) {
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

                    if (!empty($modifierData['component_id'])) {
                        // Best-effort: uang customer sudah pasti masuk di titik ini (settlement
                        // webhook), jadi tidak boleh melempar exception yang membatalkan seluruh
                        // pencatatan pembayaran hanya karena stok komponen kurang.
                        $totalModQty = $modQty * $item['quantity'];
                        $this->componentStockService->deductForModifierBestEffort(
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
                $this->componentStockService->deductForBomBestEffort(
                    $item['product_id'],
                    $item['quantity'],
                    $transaction->id,
                    $cashierId
                );
            } elseif ($product && $product->track_stock) {
                // Best-effort juga di sini — sebelumnya decrement() polos tanpa pengecekan sama
                // sekali, jadi stok bisa minus diam-diam kalau kebetulan sudah habis diambil
                // transaksi lain sebelum QRIS ini settle. Floor di 0, catat CRITICAL kalau kurang.
                $currentStock = $product->stock;
                $deduct       = min($item['quantity'], max(0, $currentStock));

                if ($deduct < $item['quantity']) {
                    \Illuminate\Support\Facades\Log::critical(
                        "OVERSOLD (settlement QRIS): Produk '{$product->name}' butuh {$item['quantity']}, " .
                        "tersisa hanya {$currentStock}. Transaksi #{$transaction->id} — barang tetap harus " .
                        "dibuatkan, cek ketersediaan fisik secara manual."
                    );
                }

                $newStock = $currentStock - $deduct;
                $product->update(['stock' => $newStock]);

                StockLog::record(
                    productId:  $product->id,
                    userId:     $cashierId,
                    type:       'sale',
                    amount:     -$deduct,
                    finalStock: $newStock,
                    note:       "QRIS Inv: {$paymentTx->invoice_number}"
                );
            }
        }

        $transaction->load(['details.modifiers', 'user', 'paymentSource', 'serviceArea']);

        // Hapus cache cart setelah berhasil
        \Illuminate\Support\Facades\Cache::forget("qris_cart_{$paymentTx->midtrans_order_id}");

        return $transaction;
    }
}
