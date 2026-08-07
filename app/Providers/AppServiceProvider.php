<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Force HTTPS ──────────────────────────────────────────────────────────
        // Cloudflare mengirim request ke origin (VPS) sebagai HTTP, meskipun
        // client mengakses via HTTPS. Laravel harus di-force agar semua
        // generated URL (asset, redirect, dll) tetap pakai https://.
        //
        // Di .env production wajib set: FORCE_HTTPS=true
        // Di .env local/staging cukup biarkan false (default).
        if ($this->app->environment('production') || (bool) env('FORCE_HTTPS', false)) {
            URL::forceScheme('https');
        }

        // ── Admin Panel: per User ID (bukan IP, karena bisa proxy kantor) ──────
        // 120 req/menit cukup untuk navigasi dan form submission admin aktif
        RateLimiter::for('admin', function (Request $request) {
            return Limit::perMinute(120)
                ->by('admin|' . ($request->user()?->id ?: $request->ip()))
                ->response(fn() => response()->json([
                    'message' => 'Terlalu banyak permintaan. Tunggu sebentar.',
                ], 429));
        });

        // ── POS Kasir: per User ID, limit tinggi karena scan cepat ───────────
        // 600 req/menit = 10 req/detik, sangat cukup untuk scan barcode
        RateLimiter::for('pos', function (Request $request) {
            return Limit::perMinute(600)
                ->by('pos|' . ($request->user()?->id ?: $request->ip()));
        });

        // ── Self Order: Public Pages (menu, checkout wizard) ─────────────────
        // 120 req/menit per IP — Livewire komponen reload wajar di batas ini
        // Livewire setiap interaksi = 1 request, bukan polling
        RateLimiter::for('self-order-page', function (Request $request) {
            return Limit::perMinute(300)
                ->by('so-page|' . ($request->user()?->id ?: $request->ip()))
                ->response(fn() => response(
                    view('errors.429', ['retryAfter' => 60]),
                    429,
                    ['Retry-After' => 60, 'Content-Type' => 'text/html']
                ));
        });

        // ── Struk Publik: halaman /receipt/{token}, publik tanpa login ───────
        RateLimiter::for('receipt-page', function (Request $request) {
            return Limit::perMinute(300)
                ->by('receipt-page|' . ($request->user()?->id ?: $request->ip()))
                ->response(fn() => response(
                    view('errors.429', ['retryAfter' => 60]),
                    429,
                    ['Retry-After' => 60, 'Content-Type' => 'text/html']
                ));
        });

        // ── Self Order API: Payment SSE & Status Check ────────────────────────
        // Strict 20 req/menit per IP — SSE harusnya persistent, bukan polling
        // Jika SSE disconnect, klien akan reconnect, 20 masih lebih dari cukup
        RateLimiter::for('self-order-api', function (Request $request) {
            return Limit::perMinute(20)
                ->by('so-api|' . $request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Terlalu banyak permintaan. Tunggu 60 detik.',
                    'retry_after' => 60,
                ], 429, ['Retry-After' => 60]));
        });

        // ── Self Order Stock API: Polling stok setiap 5 detik ────────────────
        // Klien poll setiap 5 detik = 12 req/menit. Beri buffer 36 req/menit
        // (toleransi 3× lipat untuk page reload atau tab ganda)
        RateLimiter::for('self-order-stock', function (Request $request) {
            return Limit::perMinute(36)
                ->by('so-stock|' . $request->ip())
                ->response(fn() => response()->json([
                    'message' => 'Stock refresh terlalu sering.',
                    'retry_after' => 60,
                ], 429, ['Retry-After' => 60]));
        });

        // ── Webhook Midtrans: dari IP Midtrans saja ───────────────────────────
        // Midtrans punya beberapa IP server, beri limit per order_id bukan IP
        // 10 retry per order_id per menit (Midtrans retry hingga 3x, beri buffer)
        RateLimiter::for('midtrans-webhook', function (Request $request) {
            $orderId = $request->input('order_id', $request->ip());
            return Limit::perMinute(10)
                ->by('webhook|' . $orderId);
        });

        // ── Super Admin: bypass semua permission check ────────────────────────
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });
    }
}
