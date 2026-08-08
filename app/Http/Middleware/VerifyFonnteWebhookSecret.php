<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fonnte TIDAK menyediakan signature/HMAC untuk webhook-nya (beda dengan Midtrans).
 * Verifikasi keaslian request dilakukan lewat token acak yang ditaruh di URL webhook
 * (route parameter {secret}) dan dibandingkan secara timing-safe terhadap
 * FONNTE_WEBHOOK_SECRET di .env. URL lengkap inilah yang didaftarkan di dashboard
 * Fonnte — jadi harus diperlakukan sebagai rahasia.
 */
class VerifyFonnteWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = (string) config('services.fonnte.webhook_secret');
        $provided = (string) $request->route('secret');

        if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
            Log::warning('Fonnte Webhook: secret tidak valid.', [
                'ip'   => $request->ip(),
                'path' => $request->path(),
            ]);

            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
