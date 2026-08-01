<?php

namespace App\Jobs\Payment;

use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job yang dijalankan oleh Scheduler setiap menit untuk menandai QRIS yang sudah expired.
 * Ini adalah safety net — Midtrans juga mengirim webhook expire, tapi tidak selalu tepat waktu.
 */
class CheckExpiredQrisPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function handle(): void
    {
        $expiredPayments = PaymentTransaction::getExpiredPendingQris();

        foreach ($expiredPayments as $paymentTx) {
            try {
                $paymentTx->transitionTo(
                    newStatus:   PaymentTransactionStatus::Expired,
                    triggeredBy: 'job',
                    note:        'QRIS expired — ditandai oleh CheckExpiredQrisPaymentJob',
                );

                Log::info("CheckExpiredQrisPaymentJob: PaymentTransaction [{$paymentTx->id}] ditandai expired.");
            } catch (\DomainException $e) {
                // Kemungkinan sudah di-update oleh webhook bersamaan — abaikan
                Log::info("CheckExpiredQrisPaymentJob: Skip PaymentTransaction [{$paymentTx->id}] - {$e->getMessage()}");
            } catch (\Exception $e) {
                Log::error("CheckExpiredQrisPaymentJob error untuk PaymentTransaction [{$paymentTx->id}]: " . $e->getMessage());
            }
        }
    }
}
