<?php

namespace App\Actions\Payment;

use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Log;

/**
 * Membatalkan PaymentTransaction (QRIS pending).
 * Melakukan pembatalan ke Midtrans secara best-effort (tidak throw jika Midtrans gagal).
 */
class CancelPaymentTransactionAction
{
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    public function execute(
        PaymentTransaction $paymentTx,
        int $actorId,
        string $reason = 'Dibatalkan oleh kasir'
    ): void {
        if ($paymentTx->isFinal()) {
            Log::info("CancelPaymentTransaction: PaymentTransaction [{$paymentTx->id}] sudah final ({$paymentTx->status->value}), skip.");
            return;
        }

        // Batalkan di Midtrans — best effort, tidak gagalkan flow jika Midtrans error
        $this->midtransService->cancelTransaction($paymentTx->midtrans_order_id);

        // Update status di database
        $paymentTx->transitionTo(
            newStatus:   PaymentTransactionStatus::Cancelled,
            triggeredBy: 'cashier',
            actorId:     $actorId,
            note:        $reason,
        );

        Log::info("PaymentTransaction [{$paymentTx->id}] dibatalkan oleh user [{$actorId}]. Alasan: {$reason}");
    }
}
