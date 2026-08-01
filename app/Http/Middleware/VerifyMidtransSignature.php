<?php

namespace App\Http\Middleware;

use App\Services\MidtransService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi signature key Midtrans pada setiap webhook request.
 * Formula: SHA512(order_id + status_code + gross_amount + server_key)
 */
class VerifyMidtransSignature
{
    public function __construct(
        private readonly MidtransService $midtransService
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $payload = $request->all();

        $orderId      = $payload['order_id'] ?? '';
        $statusCode   = $payload['status_code'] ?? '';
        $grossAmount  = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        if (empty($signatureKey)) {
            Log::warning('Midtrans Webhook: Tidak ada signature_key pada request.', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->midtransService->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            Log::warning('Midtrans Webhook: Signature tidak valid.', [
                'order_id' => $orderId,
                'ip'       => $request->ip(),
            ]);
            return response()->json(['message' => 'Invalid signature'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
