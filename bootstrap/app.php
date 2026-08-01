<?php

use App\Http\Middleware\SanitizeInput;
use App\Http\Middleware\SecurityHeaders;
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

        // Kecualikan /logout dan Midtrans webhook dari validasi CSRF
        $middleware->validateCsrfTokens(except: [
            '/logout',
            'logout',
            '/api/webhook/midtrans', // Midtrans tidak bisa kirim CSRF token
        ]);

        // Alias untuk Midtrans signature middleware
        $middleware->alias([
            'midtrans.signature' => VerifyMidtransSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

