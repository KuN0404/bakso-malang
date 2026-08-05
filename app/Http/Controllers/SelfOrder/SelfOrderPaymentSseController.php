<?php

namespace App\Http\Controllers\SelfOrder;

use App\Actions\SelfOrder\HandleSelfOrderWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Enums\SelfOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\SelfOrder;
use App\Services\MidtransService;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * SelfOrderPaymentSseController
 *
 * Server Sent Events untuk update status pembayaran Self Order.
 * Customer tidak perlu polling — server push status update lewat SATU koneksi.
 * Sebagai cadangan bila webhook Midtrans belum/tidak sampai, controller ini juga
 * aktif menanyakan status ke API Midtrans secara berkala dari sisi server (bukan
 * request tambahan dari browser).
 *
 * Endpoint: GET /api/self-order/payment-sse/{token}
 * Throttle: 10/menit per IP
 */
class SelfOrderPaymentSseController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtransService,
        private readonly HandleSelfOrderWebhookAction $handleWebhookAction,
    ) {}

    public function __invoke(string $token): Response
    {
        $selfOrder = SelfOrder::where('order_token', $token)
            ->with('paymentTransaction')
            ->first();

        if (!$selfOrder) {
            return response('Not Found', 404);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $maxDuration  = (int) config('self_order.sse_max_duration_seconds', 600);
        $pollInterval = (int) config('self_order.sse_poll_interval_seconds', 2);
        $activeCheckEvery = 12; // Cek aktif ke Midtrans setiap ~12 detik jika masih pending

        return response()->stream(function () use ($selfOrder, $maxDuration, $pollInterval, $activeCheckEvery) {
            $startTime = time();
            $elapsedSinceCheck = 0;

            // Set headers untuk SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no'); // Nonaktifkan buffering Nginx
            header('Connection: keep-alive');

            // Flush awal untuk koneksi
            ob_implicit_flush(true);
            @ob_end_flush();

            while (true) {
                // Cek timeout
                if ((time() - $startTime) >= $maxDuration) {
                    echo "event: timeout\n";
                    echo "data: {\"message\":\"SSE timeout, reconnect\"}\n\n";
                    flush();
                    break;
                }

                // Cek koneksi masih aktif
                if (connection_aborted()) {
                    break;
                }

                // Ambil status terbaru dari DB
                $selfOrder->refresh();

                $elapsedSinceCheck += $pollInterval;
                $paymentTx = $selfOrder->paymentTransaction;

                if ($paymentTx
                    && $paymentTx->status === PaymentTransactionStatus::Pending
                    && $elapsedSinceCheck >= $activeCheckEvery
                ) {
                    $elapsedSinceCheck = 0;

                    try {
                        $midtransRes = $this->midtransService->getTransactionStatus($paymentTx->midtrans_order_id);
                        if (isset($midtransRes['transaction_status'])) {
                            $payload = MidtransWebhookPayload::fromArray($midtransRes);
                            $this->handleWebhookAction->execute($payload);
                            $selfOrder->refresh();
                        }
                    } catch (\Throwable $e) {
                        Log::channel('self_order')->warning(
                            "SSE active check ke Midtrans gagal untuk order [{$selfOrder->order_token}]: " . $e->getMessage()
                        );
                    }
                }

                if ($selfOrder->isPaid() || $selfOrder->isProcessing() || $selfOrder->isReady() || $selfOrder->isCompleted()) {
                    echo "event: payment_success\n";
                    echo 'data: ' . json_encode([
                        'status'       => $selfOrder->status->value,
                        'status_label' => $selfOrder->status->label(),
                        'queue_number' => $selfOrder->queue_display,
                        'tracking_url' => route('self-order.tracking', ['token' => $selfOrder->order_token]),
                    ]) . "\n\n";
                    flush();
                    break;
                }

                if ($selfOrder->isCancelled() || $selfOrder->isExpired()) {
                    echo "event: payment_failed\n";
                    echo 'data: ' . json_encode([
                        'status'  => $selfOrder->status->value,
                        'message' => $selfOrder->status->label(),
                    ]) . "\n\n";
                    flush();
                    break;
                }

                // Keep-alive setiap 15 detik
                if ((time() - $startTime) % 15 === 0) {
                    echo "event: keep_alive\n";
                    echo 'data: ' . json_encode(['ts' => time()]) . "\n\n";
                    flush();
                }

                sleep($pollInterval);
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
