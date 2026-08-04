<?php

namespace App\Jobs\SelfOrder;

use App\Enums\SelfOrderStatus;
use App\Models\SelfOrder;
use App\Services\StockReservationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CheckExpiredSelfOrderReservationsJob
 *
 * Berjalan setiap menit (scheduler) untuk:
 *  1. Mengidentifikasi Self Orders yang QRIS-nya expired
 *  2. Mengidentifikasi Self Orders "Bayar di Tempat" yang timeout (30 menit default)
 *  3. Melepas stock reservations dari order yang expired/timeout
 *
 * Ini adalah safety net — Midtrans juga kirim webhook expire, tapi tidak selalu tepat waktu.
 */
class CheckExpiredSelfOrderReservationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function handle(StockReservationService $reservationService): void
    {
        $expiredOrders = SelfOrder::getExpiredPending();

        if ($expiredOrders->isEmpty()) {
            return;
        }

        Log::channel('self_order')->info(
            "CheckExpiredSelfOrderReservationsJob: {$expiredOrders->count()} order expired ditemukan."
        );

        foreach ($expiredOrders as $selfOrder) {
            try {
                DB::transaction(function () use ($selfOrder, $reservationService) {
                    // Re-lock untuk memastikan masih expired
                    $locked = SelfOrder::where('id', $selfOrder->id)
                        ->whereIn('status', [
                            SelfOrderStatus::PendingPayment->value,
                            SelfOrderStatus::WaitingPayment->value,
                        ])
                        ->lockForUpdate()
                        ->first();

                    if (!$locked) {
                        return; // Sudah diproses oleh proses lain
                    }

                    $locked->update([
                        'status'           => SelfOrderStatus::Expired->value,
                        'cancelled_reason' => $locked->payment_method === 'qris'
                            ? 'QRIS kadaluarsa — ditandai oleh sistem'
                            : 'Timeout pembayaran di kasir — ditandai oleh sistem',
                        'cancelled_at'     => now(),
                    ]);

                    // Release stock reservations
                    $reservationService->releaseForSelfOrder($locked);

                    // Jika payment transaction masih pending, transisi ke expired
                    if ($locked->paymentTransaction?->isPending()) {
                        $locked->paymentTransaction->transitionTo(
                            \App\Enums\PaymentTransactionStatus::Expired,
                            'job',
                            note: 'QRIS expired — ditandai oleh CheckExpiredSelfOrderReservationsJob'
                        );
                    }
                });

                Log::channel('self_order')->info(
                    "CheckExpiredSelfOrderReservationsJob: SelfOrder [{$selfOrder->id}] ditandai expired, reservation dilepas."
                );

            } catch (\DomainException $e) {
                // Status sudah berubah oleh proses lain (webhook) — diabaikan
                Log::channel('self_order')->info(
                    "CheckExpiredSelfOrderReservationsJob: Skip SelfOrder [{$selfOrder->id}] - {$e->getMessage()}"
                );
            } catch (\Exception $e) {
                Log::channel('self_order')->error(
                    "CheckExpiredSelfOrderReservationsJob error untuk SelfOrder [{$selfOrder->id}]: " . $e->getMessage()
                );
            }
        }
    }
}
