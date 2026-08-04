<?php

namespace App\Http\Controllers\Payment;

use App\Actions\Payment\HandleMidtransWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * SSE (Server-Sent Events) Controller untuk real-time update status QRIS.
 *
 * Frontend membuka SATU koneksi ke /api/payment/qris-status/{orderId} dan tidak
 * melakukan polling terpisah dari client. Selama koneksi terbuka, controller ini:
 *  1. Mengecek status di database setiap 2 detik (diisi oleh webhook Midtrans jika sampai).
 *  2. Sebagai cadangan bila webhook belum/tidak sampai, setiap ~12 detik controller
 *     JUGA aktif menanyakan status ke API Midtrans secara langsung (server-to-server,
 *     bukan request tambahan dari browser) sehingga pembayaran tetap terdeteksi otomatis
 *     tanpa butuh client polling maupun klik tombol manual.
 *
 * NGINX Config yang diperlukan pada vhost (AAPanel):
 *   fastcgi_buffering off;
 *   proxy_buffering off;
 */
class QrisStatusSseController
{
    public function __construct(
        private readonly MidtransService $midtransService,
        private readonly HandleMidtransWebhookAction $handleWebhookAction,
    ) {}

    public function __invoke(Request $request, string $orderId): StreamedResponse
    {
        // Pastikan kasir yang memiliki QRIS ini
        $paymentTx = PaymentTransaction::where('midtrans_order_id', $orderId)->first();

        if (!$paymentTx || $paymentTx->created_by !== auth()->id()) {
            abort(403, 'Forbidden');
        }

        return response()->stream(function () use ($paymentTx, $orderId) {
            $maxDuration       = 300; // 5 menit max SSE connection
            $startTime         = time();
            $interval          = 2;   // Cek DB setiap 2 detik
            $activeCheckEvery  = 12;  // Cek aktif ke Midtrans setiap ~12 detik (jaga-jaga webhook belum sampai)
            $elapsedSinceCheck = 0;

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
                $elapsedSinceCheck += $interval;

                // Refresh dari database
                $paymentTx->refresh();
                $currentStatus = $paymentTx->status;

                // Cadangan: jika masih pending dan webhook belum mengubah status setelah
                // beberapa detik, tanyakan langsung ke Midtrans dari sisi server (bukan
                // request baru dari client) lalu proses seperti webhook biasa.
                if ($currentStatus === PaymentTransactionStatus::Pending && $elapsedSinceCheck >= $activeCheckEvery) {
                    $elapsedSinceCheck = 0;
                    $this->activelyCheckMidtrans($paymentTx, $orderId);
                    $paymentTx->refresh();
                    $currentStatus = $paymentTx->status;
                }

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

    private function activelyCheckMidtrans(PaymentTransaction $paymentTx, string $orderId): void
    {
        try {
            $midtransRes = $this->midtransService->getTransactionStatus($orderId);

            if (isset($midtransRes['transaction_status'])) {
                $payload = MidtransWebhookPayload::fromArray($midtransRes);
                $this->handleWebhookAction->execute($payload);
            }
        } catch (\Throwable $e) {
            Log::warning("SSE active check ke Midtrans gagal untuk order [{$orderId}]: " . $e->getMessage());
        }
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
