<?php

namespace App\Actions\SelfOrder;

use App\Enums\PaymentTransactionStatus;
use App\Enums\SelfOrderStatus;
use App\Exceptions\NoOpenShiftException;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\SelfOrder;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\ReportSyncService;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AcceptSelfOrderPaymentAction
 *
 * Kasir mengkonfirmasi pembayaran tunai untuk Self Order "Bayar di Tempat".
 *
 * Flow:
 *  1. Lock SelfOrder (cegah double konfirmasi)
 *  2. Validasi status (harus waiting_payment)
 *  3. Buat PaymentTransaction (status=paid)
 *  4. Buat Transaction POS (status=completed)
 *  5. Konversi reservation → actual stock reduction
 *  6. Update SelfOrder → paid
 *  7. Sync laporan
 */
class AcceptSelfOrderPaymentAction
{
    public function __construct(
        private readonly StockReservationService $reservationService,
        private readonly ReportSyncService $reportSyncService,
    ) {}

    /**
     * @throws \DomainException|\Exception
     */
    public function execute(int $selfOrderId, int $cashierId, float $paidAmount): Transaction
    {
        return DB::transaction(function () use ($selfOrderId, $cashierId, $paidAmount) {
            // -- Lock SelfOrder untuk mencegah double konfirmasi --
            $selfOrder = SelfOrder::with('items.modifiers')
                ->where('id', $selfOrderId)
                ->lockForUpdate()
                ->firstOrFail();

            // -- Validasi status --
            if (!$selfOrder->isWaitingPayment()) {
                throw new \DomainException(
                    "Order #{$selfOrder->queue_display} tidak bisa dikonfirmasi. " .
                    "Status saat ini: {$selfOrder->status->label()}."
                );
            }

            // -- Pastikan kasir yang memproses menjadi owner (atau tolak jika sudah diambil kasir lain) --
            $shift = Shift::where('user_id', $cashierId)->where('status', 'open')->latest()->first();

            if (!$shift) {
                throw new NoOpenShiftException('Anda belum membuka shift. Buka shift terlebih dahulu sebelum mengonfirmasi pembayaran.');
            }

            if (!$selfOrder->isClaimed()) {
                // $shift sudah dipastikan tidak null oleh guard di atas.
                $selfOrder->claimBy($cashierId, $shift->id);
            } elseif ($selfOrder->processed_by !== $cashierId) {
                $ownerName = $selfOrder->processedBy?->name ?? "kasir lain";
                throw new \DomainException("Order #{$selfOrder->queue_display} sudah diproses/diambil oleh {$ownerName}.");
            }

            $invoiceNumber = 'SO-' . now()->format('Ymd') . '-' . str_pad($selfOrder->queue_number, 4, '0', STR_PAD_LEFT);

            // -- Buat PaymentTransaction (paid langsung) --
            $paymentSource = PaymentSource::getDefaultCash() ?? PaymentSource::active()->first();
            $paymentTx     = PaymentTransaction::create([
                'self_order_id'     => $selfOrder->id,
                'invoice_number'    => $invoiceNumber,
                'midtrans_order_id' => 'CASH-' . $invoiceNumber,
                'payment_method'    => 'cash_on_counter',
                'amount'            => $selfOrder->total,
                'status'            => PaymentTransactionStatus::Paid->value,
                'source'            => 'self_order',
                'paid_at'           => now(),
                'created_by'        => $cashierId,
            ]);

            $paymentTx->statusLogs()->create([
                'from_status'  => null,
                'to_status'    => PaymentTransactionStatus::Paid->value,
                'triggered_by' => 'cashier',
                'actor_id'     => $cashierId,
                'note'         => "Konfirmasi bayar di kasir. Nominal: Rp " . number_format($paidAmount, 0, ',', '.'),
            ]);

            // -- Buat Transaction POS --
            $transaction = Transaction::create([
                'user_id'                => $cashierId,
                'shift_id'               => $shift?->id,
                'payment_source_id'      => $paymentSource?->id,
                'payment_transaction_id' => $paymentTx->id,
                'self_order_id'          => $selfOrder->id,
                'invoice_number'         => $invoiceNumber,
                'queue_number'           => $selfOrder->queue_number,
                'subtotal'               => $selfOrder->subtotal,
                'discount_amount'        => 0,
                'tax_amount'             => $selfOrder->tax_amount,
                'total'                  => $selfOrder->total,
                'paid_amount'            => $paidAmount,
                'change_amount'          => max(0, $paidAmount - $selfOrder->total),
                'payment_method'         => 'cash',
                'payment_gateway_status' => null,
                'source'                 => 'self_order',
                'status'                 => 'completed',
                'customer_name'          => $selfOrder->customer_name,
                'order_type'             => $selfOrder->order_type,
                'notes'                  => $selfOrder->notes,
            ]);

            $paymentTx->update(['transaction_id' => $transaction->id]);

            // -- Buat TransactionDetails --
            foreach ($selfOrder->items as $item) {
                $detail = $transaction->details()->create([
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product_name,
                    'unit_price'     => $item->unit_price,
                    'quantity'       => $item->quantity,
                    'modifier_total' => $item->modifier_total,
                    'subtotal'       => $item->subtotal,
                ]);

                foreach ($item->modifiers as $mod) {
                    $detail->modifiers()->attach($mod->modifier_id, [
                        'modifier_name'    => $mod->modifier_name,
                        'price_adjustment' => $mod->price_adjustment,
                        'quantity'         => $mod->quantity,
                    ]);
                }
            }

            // -- Konversi reservation → actual stock reduction --
            $this->reservationService->convertForSelfOrder(
                selfOrder:     $selfOrder,
                actorUserId:   $cashierId,
                transactionId: $transaction->id,
                invoiceNumber: $invoiceNumber
            );

            // -- Update SelfOrder --
            $selfOrder->update([
                'status'                 => SelfOrderStatus::Paid->value,
                'paid_at'                => now(),
                'invoice_number'         => $invoiceNumber,
                'transaction_id'         => $transaction->id,
                'payment_transaction_id' => $paymentTx->id,
                'processed_by'           => $cashierId,
                'shift_id'               => $shift?->id,
                'claimed_at'             => $selfOrder->claimed_at ?? now(),
            ]);

            // -- Sync laporan --
            $this->reportSyncService->syncTransaction($transaction);

            // -- Kirim email struk jika customer punya email --
            if ($selfOrder->customer_email && config('self_order.send_email_receipt', true)) {
                try {
                    \Illuminate\Support\Facades\Mail::to($selfOrder->customer_email)
                        ->queue(new \App\Mail\SelfOrderReceiptMail($selfOrder->load('items.modifiers')));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::channel('self_order')->warning(
                        "Gagal kirim email struk ke [{$selfOrder->customer_email}]: " . $e->getMessage()
                    );
                }
            }

            Log::channel('self_order')->info(
                "SelfOrder [{$selfOrder->id}] dikonfirmasi bayar oleh Kasir [{$cashierId}]. Transaction [{$transaction->id}] dibuat."
            );

            return $transaction->load(['details.modifiers', 'paymentSource']);
        });
    }
}
