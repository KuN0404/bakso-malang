<?php

use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\VerifyFonnteWebhookSecret;
use App\Http\Middleware\VerifyMidtransSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // ── Trust Proxies: Wajib untuk Cloudflare ─────────────────────────────
        // Cloudflare menggunakan IP publik (bukan private subnets), sehingga
        // default '127.0.0.1,...' TIDAK mencakup Cloudflare.
        //
        // Di .env production (VPS + Cloudflare) wajib set: TRUSTED_PROXIES=*
        // Di .env local: biarkan default (private subnets).
        //
        // trustHeaders: eksplisit header mana yang dipercaya dari proxy.
        // Mencegah spoofing jika TRUSTED_PROXIES tidak di-set dengan benar.
        $trustedProxies = env('TRUSTED_PROXIES', '127.0.0.1,::1,10.0.0.0/8,172.16.0.0/12,192.168.0.0/16');
        $middleware->trustProxies(
            at: $trustedProxies === '*' ? '*' : array_map('trim', explode(',', $trustedProxies)),
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
                   | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        // Security headers on every response
        $middleware->append(SecurityHeaders::class);

        // XSS / input sanitation on web requests
        $middleware->web(append: [
            SanitizeInput::class,
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: '/admin'
        );

        // Kecualikan /logout, Midtrans webhook, dan Fonnte webhook dari validasi CSRF
        $middleware->validateCsrfTokens(except: [
            '/logout',
            'logout',
            '/api/webhook/midtrans',          // Midtrans POS webhook
            '/api/webhook/midtrans/self-order', // Midtrans Self Order webhook
            '/api/webhook/fonnte/*',          // Fonnte WhatsApp webhook (device-status & message-status)
        ]);

        // Alias untuk middleware verifikasi webhook
        $middleware->alias([
            'midtrans.signature'  => VerifyMidtransSignature::class,
            'fonnte.webhook.secret' => VerifyFonnteWebhookSecret::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

