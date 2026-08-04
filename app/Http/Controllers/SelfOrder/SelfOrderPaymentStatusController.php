<?php

namespace App\Http\Controllers\SelfOrder;

use App\Http\Controllers\Controller;
use App\Models\SelfOrder;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * SelfOrderPaymentStatusController
 *
 * Fallback manual check untuk status pembayaran.
 * Digunakan jika SSE tidak tersedia atau customer klik tombol "Cek Status".
 *
 * Endpoint: GET /api/self-order/payment-status/{token}
 * Throttle: 10/menit per IP
 */
class SelfOrderPaymentStatusController extends Controller
{
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    public function __invoke(string $token): JsonResponse
    {
        $selfOrder = SelfOrder::where('order_token', $token)
            ->with('paymentTransaction')
            ->first();

        if (!$selfOrder) {
            return response()->json(['error' => 'Order tidak ditemukan.'], 404);
        }

        // Jika masih pending dan QRIS → coba cek status dari Midtrans API
        if ($selfOrder->isPendingPayment() && $selfOrder->paymentTransaction && $selfOrder->payment_method === 'qris') {
            try {
                $midtransStatus = $this->midtransService->checkStatus(
                    $selfOrder->paymentTransaction->midtrans_order_id
                );

                // Jika Midtrans sudah settlement tapi webhook belum datang
                if (in_array($midtransStatus['transaction_status'] ?? '', ['settlement', 'capture'])) {
                    Log::channel('self_order_payment')->info(
                        "Manual check: Order [{$selfOrder->id}] settlement di Midtrans tapi belum di sistem. Dispatch handler."
                    );

                    // Dispatch webhook handler via queue
                    \App\Jobs\SelfOrder\ProcessMidtransSettlementJob::dispatch($selfOrder->paymentTransaction->midtrans_order_id);
                }
            } catch (\Exception $e) {
                Log::channel('self_order')->warning("Midtrans API check gagal: " . $e->getMessage());
            }

            // Refresh status dari DB
            $selfOrder->refresh();
        }

        $paymentTx = $selfOrder->paymentTransaction;
        $expiresIn = null;

        if ($paymentTx && $paymentTx->expired_at) {
            $expiresIn = max(0, now()->diffInSeconds($paymentTx->expired_at, false));
        }

        return response()->json([
            'status'        => $selfOrder->status->value,
            'status_label'  => $selfOrder->status->label(),
            'payment_status' => $paymentTx?->status ?? null,
            'queue_number'  => $selfOrder->queue_display,
            'expires_in'    => $expiresIn,
            'is_paid'       => $selfOrder->isPaid(),
            'is_expired'    => $selfOrder->isExpired(),
            'tracking_url'  => $selfOrder->isPaid()
                ? route('self-order.tracking', ['token' => $selfOrder->order_token])
                : null,
        ]);
    }
}
