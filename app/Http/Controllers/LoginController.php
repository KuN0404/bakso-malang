<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $ip = $request->ip();
        $throttleKey = 'login_attempts:' . $ip;
        $backoffKey = 'login_backoff_level:' . $ip;
        $activeLockoutKey = 'login_active_lockout:' . $ip;

        // 1. Check Custom Lockout First
        if (cache()->has($activeLockoutKey)) {
            // Get exact time remaining
            // Actually cache TTL isn't easily readable in seconds unless we stored the expiry timestamp value.
            // Let's store expiry timestamp in value.
            $expiry = cache()->get($activeLockoutKey);
            $seconds = now()->diffInSeconds($expiry, false); // false = return negative if past
            
            if ($seconds > 0) {
                 throw ValidationException::withMessages([
                    'username' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam ' . ceil($seconds / 60) . ' menit.',
                ]);
            }
        }

        // Verify reCAPTCHA
        if ($request->filled('g-recaptcha-response')) {
            $this->verifyRecaptcha($request->input('g-recaptcha-response'));
        } else {
             // Optional: Force recaptcha? User agreed.
             // If config is missing, maybe skip? But let's enforce if checkbox is there.
             if (config('services.recaptcha.site_key')) {
                 throw ValidationException::withMessages(['g-recaptcha-response' => 'Harap centang reCAPTCHA.']);
             }
        }

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            
            // Clear throttling on success
            RateLimiter::clear($throttleKey);
            cache()->forget($backoffKey);
            cache()->forget($activeLockoutKey);
            
            return redirect()->intended('/admin');
        }

        // Login failed: Increment Rate Limiter
        // Allow 3 attempts before evaluating lockout
        RateLimiter::hit($throttleKey, 60); // 60s decay just for standard counting
        
        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            // Reached 3 failures.
            // Increment backoff level
            $level = cache()->get($backoffKey, 0) + 1;
            cache()->put($backoffKey, $level, now()->addHours(24)); // Level resets after 24h idle
            
            // Determine duration
            $minutes = match ($level) {
                1 => 3,
                2 => 10,
                3 => 30,
                default => 60,
            };
            
            // Set Lockout
            cache()->put($activeLockoutKey, now()->addMinutes($minutes), now()->addMinutes($minutes));
            
            // Clear the 1-min attempts so we don't double count next time, logic relies on activeLockoutKey now
            RateLimiter::clear($throttleKey);

            throw ValidationException::withMessages([
                'username' => "Login gagal 3x. IP Anda diblokir selama $minutes menit.",
            ]);
        }

        throw ValidationException::withMessages([
            'username' => 'Username atau password salah',
        ]);
    }
    
    protected function verifyRecaptcha($token)
    {
        $secret = config('services.recaptcha.secret_key');
        if (!$secret) return; // Skip if not configured
        
        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secret,
            'response' => $token,
        ]);
        
        if (!$response->json('success')) {
             throw ValidationException::withMessages([
                'g-recaptcha-response' => 'Validasi reCAPTCHA gagal.',
            ]);
        }
    }
}
