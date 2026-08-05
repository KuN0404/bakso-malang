<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * HTTP security headers to protect against XSS, clickjacking,
     * MIME sniffing, and other common web attacks.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Clickjacking Protection ─────────────────────────────────────────
        // SAMEORIGIN: izinkan embed dari origin yang sama saja.
        // Dipilih karena Google Maps iframe di landing page butuh frame-src,
        // dan halaman self-order mungkin di-embed dari domain yang sama.
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // ── MIME Sniffing & XSS Protection ───────────────────────────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── Referrer & Permissions ─────────────────────────────────────
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()'); // tambah payment=()

        // ── Sembunyikan tech stack dari attacker ───────────────────────────
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // ── Content-Security-Policy ──────────────────────────────────────
        // Domain yang dipakai:
        //   cdn.tailwindcss.com  → Tailwind CDN (print views, layouts)
        //   unpkg.com            → Lucide Icons (admin, pos, customer layouts)
        //   cdn.jsdelivr.net     → Flatpickr (date picker di admin)
        //   challenges.cloudflare.com → Cloudflare Turnstile captcha
        //   fonts.googleapis.com / fonts.gstatic.com → Google Fonts
        //   maps.googleapis.com / www.google.com → Google Maps embed (landing page)
        //   Alpine.js → di-bundle oleh Livewire, tidak perlu CDN terpisah
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' " .
                "https://challenges.cloudflare.com " .
                "https://unpkg.com " .
                "https://cdn.tailwindcss.com " .
                "https://fonts.googleapis.com " .
                "https://cdn.jsdelivr.net " .
                "https://maps.googleapis.com; " .
            "style-src 'self' 'unsafe-inline' " .
                "https://fonts.googleapis.com " .
                "https://fonts.gstatic.com " .
                "https://cdn.jsdelivr.net " .
                "https://unpkg.com; " .  // Lucide icons bisa inject inline style
            "font-src 'self' https://fonts.gstatic.com; " .
            "frame-src " .
                "https://challenges.cloudflare.com " .
                "https://www.google.com " .
                "https://maps.googleapis.com; " .
            "img-src 'self' data: https: https://maps.gstatic.com https://maps.googleapis.com; " .
            "connect-src 'self' https://maps.googleapis.com;"
        );

        // ── HSTS: hanya di production (wajib untuk Cloudflare Full Strict) ─────
        // max-age=31536000 = 1 tahun. preload agar browser cache HSTS.
        // JANGAN aktifkan di local/staging — akan lock HTTPS selama 1 tahun.
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
