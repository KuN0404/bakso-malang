<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that should never be sanitized (e.g., passwords, tokens).
     */
    protected array $except = [
        'password',
        'password_confirmation',
        '_token',
        'cf-turnstile-response',
        'g-recaptcha-response',
        'components',
        'snapshot',
        'updates',
        'calls',
        'fingerprint',
        'serverMemo',
        'uploads',
    ];

    /**
     * Sanitize all string inputs to protect against XSS and SQL injection
     * by stripping dangerous HTML tags and encoding special characters.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $this->sanitize($input);
        $request->merge($input);

        return $next($request);
    }

    protected function sanitize(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_array($value)) {
                $this->sanitize($value);
            } elseif (is_string($value)) {
                // Strip dangerous tags, encode special HTML characters
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8', false);
            }
        }
    }
}
