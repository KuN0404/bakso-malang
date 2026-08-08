<?php

namespace App\Http\Controllers;

use App\Models\Component;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $ip             = $request->ip();
        $username       = strtolower(trim($request->input('username', '')));
        $userIdentifier = sha1($ip . '|' . $username);

        // ── Kunci untuk Lapis 1 (Spesifik: User + IP) ──
        $attemptKey  = 'login_attempts:'  . $userIdentifier;
        $levelKey    = 'login_lock_level:' . $userIdentifier;
        $lockKey     = 'login_locked_until:' . $userIdentifier;

        // ── Kunci untuk Lapis 2 (Global: Hanya IP) ──
        $globalAttemptKey = 'login_global_attempts:' . $ip;
        $globalLockKey    = 'login_global_locked_until:' . $ip;

        // ── 1. Cek Lockout Lapis 2 (Global IP) Terlebih Dahulu ──────────────────
        if (cache()->has($globalLockKey)) {
            $until   = cache()->get($globalLockKey);
            $seconds = now()->diffInSeconds($until, false);

            if ($seconds > 0) {
                $mins = (int) ceil($seconds / 60);
                throw ValidationException::withMessages([
                    'username' => "Aktivitas mencurigakan terdeteksi dari koneksi Anda. Silakan coba lagi dalam {$mins} menit."
                ]);
            }
            cache()->forget($globalLockKey);
            cache()->forget($globalAttemptKey);
        }

        // ── 2. Cek Lockout Lapis 1 (User + IP) ──────────────────────────────────
        if ($username && cache()->has($lockKey)) {
            $until   = cache()->get($lockKey);
            $seconds = now()->diffInSeconds($until, false);

            if ($seconds > 0) {
                $mins = (int) ceil($seconds / 60);
                $secs = $seconds % 60;
                $msg  = $mins > 0
                    ? "Terlalu banyak percobaan untuk username ini. Coba lagi dalam {$mins} menit {$secs} detik."
                    : "Terlalu banyak percobaan untuk username ini. Coba lagi dalam {$secs} detik.";

                throw ValidationException::withMessages(['username' => $msg]);
            }

            // Lockout expired — bersihkan
            cache()->forget($lockKey);
            cache()->forget($attemptKey);
        }

        // ── 3. Validasi input ────────────────────────────────────────────────
        $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:1'],
        ]);

        // ── 4. Verifikasi Cloudflare Turnstile ───────────────────────────────
        try {
            $this->verifyTurnstile($request);
        } catch (ValidationException $e) {
            // Jika captcha gagal/kosong, tetap naikkan counter salah login agar terblokir jika brute force!
            $this->incrementAttempts($ip, $attemptKey, $levelKey, $lockKey, $globalAttemptKey, $globalLockKey, $username);
            throw $e;
        }

        Log::info('Debugging login inputs', [
            'username_input' => $request->input('username'),
            'username_clean' => $username,
            'password_length' => strlen($request->input('password') ?? ''),
        ]);

        // ── 5. Attempt autentikasi ───────────────────────────────────────────
        if (Auth::attempt([
            'username' => $username, // Gunakan yang sudah dibersihkan (case-insensitive & trim)
            'password' => $request->input('password'),
        ], false)) {
            $request->session()->regenerate();

            // Bersihkan semua counter jika berhasil login
            cache()->forget($attemptKey);
            cache()->forget($levelKey);
            cache()->forget($lockKey);
            cache()->forget($globalAttemptKey);
            cache()->forget($globalLockKey);

            $this->flashLowStockAlert($request);

            return redirect()->intended('/admin');
        }

        // ── 6. Login gagal — Catat di kedua Lapis ───────────────────────────
        $this->incrementAttempts($ip, $attemptKey, $levelKey, $lockKey, $globalAttemptKey, $globalLockKey, $username);

        $attempts = (int) cache()->get($attemptKey, 0);
        $remaining = 3 - ($attempts % 3);
        if ($remaining === 0) { $remaining = 3; }
        
        throw ValidationException::withMessages([
            'username' => "Username atau password salah. {$remaining} percobaan lagi sebelum diblokir.",
        ]);
    }

    /**
     * Helper untuk menaikkan counter kegagalan di Lapis 1 & Lapis 2
     */
    protected function incrementAttempts($ip, $attemptKey, $levelKey, $lockKey, $globalAttemptKey, $globalLockKey, $username): void
    {
        // A. Catat Lapis 1 (User + IP)
        $attempts = (int) cache()->get($attemptKey, 0) + 1;
        cache()->put($attemptKey, $attempts, now()->addHours(24));

        // B. Catat Lapis 2 (Global IP)
        $globalAttempts = (int) cache()->get($globalAttemptKey, 0) + 1;
        cache()->put($globalAttemptKey, $globalAttempts, now()->addMinutes(15));

        Log::warning('Login attempt recorded as failure', [
            'ip'              => $ip,
            'username'        => $username,
            'attempts'        => $attempts,
            'global_attempts' => $globalAttempts,
        ]);

        // Cek Pemicu Blokir Lapis 2 (Global IP)
        if ($globalAttempts >= 10) {
            cache()->put($globalLockKey, now()->addMinutes(15), now()->addMinutes(15));
            Log::warning('IP locked out globally due to potential brute force', ['ip' => $ip]);
            throw ValidationException::withMessages([
                'username' => 'Aktivitas mencurigakan terdeteksi dari koneksi Anda. Akses diblokir selama 15 menit.',
            ]);
        }

        // Cek Pemicu Blokir Lapis 1 (User + IP)
        if ($attempts % 3 === 0) {
            $level   = (int) cache()->get($levelKey, 0) + 1;
            $minutes = match (true) {
                $level === 1 => 1,
                $level === 2 => 3,
                $level === 3 => 10,
                $level === 4 => 30,
                default      => 60,
            };

            cache()->put($levelKey,  $level,                   now()->addHours(24));
            cache()->put($lockKey,   now()->addMinutes($minutes), now()->addMinutes($minutes));

            // Reset counter spesifik agar bisa menghitung 3x lagi setelah terlepas blokir
            cache()->forget($attemptKey);

            throw ValidationException::withMessages([
                'username' => "Terlalu banyak kegagalan. IP diblokir selama {$minutes} menit untuk username ini.",
            ]);
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        // Invalidate session and regenerate CSRF token to prevent 419 on re-use
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Flash jumlah item stok menipis/habis ke session sekali (satu-arah), untuk
     * dibaca & ditampilkan sebagai toast oleh Dashboard::mount() setelah redirect.
     */
    protected function flashLowStockAlert(Request $request): void
    {
        // lowStock() (stock <= minimum_stock) sudah mencakup item yang habis (stock <= 0)
        // selama minimum_stock >= 0, jadi TIDAK dijumlahkan dengan outOfStock() —
        // menjumlahkan keduanya akan menghitung ganda item yang sekaligus habis & menipis.
        $total = Ingredient::lowStock()->count() + Component::lowStock()->count();

        if ($total > 0) {
            $request->session()->flash('low_stock_alert_count', $total);
        }
    }

    // ─── Helpers ────────────────────────────────────────────────────────────────

    protected function verifyTurnstile(Request $request): void
    {
        $siteKey   = config('services.turnstile.site_key');
        $secretKey = config('services.turnstile.secret_key');

        // Lewati jika Turnstile tidak dikonfigurasi sama sekali di .env (misal dikosongkan atau dikomentari)
        if (! $siteKey || ! $secretKey) {
            return;
        }

        $host = $request->getHost();
        $isLocal = in_array($host, ['127.0.0.1', 'localhost']);

        // Jika localhost, override secret_key menggunakan dummy key agar cocok dengan dummy sitekey di view
        // Dummy
        if ($isLocal) {
            $secretKey = '1x0000000000000000000000000000000AA';
        }

        $token = $request->input('cf-turnstile-response');

        if (! $token) {
            throw ValidationException::withMessages([
                'captcha' => 'Harap selesaikan verifikasi keamanan (Captcha).',
            ]);
        }

        // Verifikasi SSL aktif otomatis di production, dan mati di localhost / local development
        $response = Http::asForm()
            ->withOptions([
                'verify' => ! $isLocal && ! app()->isLocal(),
            ])
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret'   => $secretKey,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            $errorCodes = $response->json('error-codes', []);
            Log::warning('Turnstile verification failed', ['error-codes' => $errorCodes, 'ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'captcha' => 'Verifikasi keamanan gagal. Silakan coba lagi.',
            ]);
        }
    }
}
