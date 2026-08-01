<?php

namespace App\Http\Controllers\Payment;

use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE (Server-Sent Events) Controller untuk real-time update status QRIS.
 *
 * Frontend membuka koneksi ke /api/payment/qris-status/{orderId}
 * Controller akan polling database setiap 2 detik dan mengirim event saat status berubah.
 *
 * NGINX Config yang diperlukan pada vhost (AAPanel):
 *   fastcgi_buffering off;
 *   proxy_buffering off;
 */
class QrisStatusSseController
{
    public function __invoke(Request $request, string $orderId): StreamedResponse
    {
        // Pastikan kasir yang memiliki QRIS ini
        $paymentTx = PaymentTransaction::where('midtrans_order_id', $orderId)->first();

        if (!$paymentTx || $paymentTx->created_by !== auth()->id()) {
            abort(403, 'Forbidden');
        }

        return response()->stream(function () use ($paymentTx, $orderId) {
            $maxDuration = 300; // 5 menit max SSE connection
            $startTime   = time();
            $interval    = 2;   // Cek setiap 2 detik

            // Kirim event awal
            $this->sendSseEvent('connected', [
                'order_id'   => $orderId,
                'status'     => $paymentTx->status->value,
                'expired_at' => $paymentTx->expired_at?->toISOString(),
            ]);

            while (true) {
                // Cek durasi maksimum
                if ((time() - $startTime) >= $maxDuration) {
                    $this->sendSseEvent('timeout', ['message' => 'SSE connection timeout']);
                    break;
                }

                // Cek koneksi browser masih ada
                if (connection_aborted()) {
                    break;
                }

                sleep($interval);

                // Refresh dari database
                $paymentTx->refresh();
                $currentStatus = $paymentTx->status;

                if ($currentStatus === PaymentTransactionStatus::Paid) {
                    $this->sendSseEvent('paid', [
                        'status'         => 'paid',
                        'transaction_id' => $paymentTx->transaction_id,
                        'invoice_number' => $paymentTx->invoice_number,
                    ]);
                    break;
                }

                if ($currentStatus === PaymentTransactionStatus::Expired || $paymentTx->isQrisExpiredByTime()) {
                    $this->sendSseEvent('expired', [
                        'status'  => 'expired',
                        'message' => 'QRIS telah kadaluarsa',
                    ]);
                    break;
                }

                if (in_array($currentStatus, [PaymentTransactionStatus::Failed, PaymentTransactionStatus::Cancelled])) {
                    $this->sendSseEvent('failed', [
                        'status'  => $currentStatus->value,
                        'message' => 'Pembayaran gagal atau dibatalkan',
                    ]);
                    break;
                }

                // Masih pending — kirim heartbeat
                $this->sendSseEvent('pending', [
                    'status'     => 'pending',
                    'expires_in' => $paymentTx->expired_at ? max(0, $paymentTx->expired_at->diffInSeconds(now(), false) * -1) : null,
                ]);

                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }

        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no', // Untuk Nginx: disable buffering
            'Connection'        => 'keep-alive',
        ]);
    }

    private function sendSseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: ' . json_encode($data) . "\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
