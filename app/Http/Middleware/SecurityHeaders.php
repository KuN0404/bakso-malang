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

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://challenges.cloudflare.com https://unpkg.com https://cdn.tailwindcss.com https://fonts.googleapis.com https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.gstatic.com https://cdn.jsdelivr.net; " .
            "font-src 'self' https://fonts.gstatic.com; " .
            "frame-src https://challenges.cloudflare.com; " .
            "img-src 'self' data: https:; " .
            "connect-src 'self';"
        );

        // Only add HSTS in production
        if (app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
