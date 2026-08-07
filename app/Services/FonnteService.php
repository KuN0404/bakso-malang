<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Integrasi WhatsApp satu arah (outbound only) lewat Fonnte (docs.fonnte.com).
 * Tidak ada endpoint/route untuk menerima pesan masuk — service ini murni
 * mengirim pesan & mengelola koneksi device (QR pairing).
 */
class FonnteService
{
    private const BASE_URL = 'https://api.fonnte.com';

    private readonly ?string $token;
    private readonly ?string $accountToken;
    private readonly int $timeout;

    public function __construct(?string $token = null, ?string $accountToken = null, ?int $timeout = null)
    {
        $this->token = $token ?? config('services.fonnte.token');
        // Account token dipakai khusus untuk endpoint /qr (pairing device baru).
        // Kalau belum diisi, fallback ke device token — beberapa akun Fonnte
        // hanya punya satu token yang berlaku untuk keduanya.
        $this->accountToken = $accountToken ?? (config('services.fonnte.account_token') ?: $this->token);
        $this->timeout = $timeout ?? (int) config('services.fonnte.timeout', 15);
    }

    public function isConfigured(): bool
    {
        return filled($this->token);
    }

    /**
     * Ambil QR code (base64 PNG) untuk menyambungkan device ke Fonnte.
     * Kalau device sudah tersambung, Fonnte membalas status:false dengan
     * reason "device already connect" — bukan error, tetap dilaporkan apa
     * adanya lewat array balikan supaya caller bisa membedakan.
     */
    public function getQrCode(): array
    {
        if (!filled($this->accountToken)) {
            return ['status' => false, 'reason' => 'Token Fonnte belum diset di server (.env)'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->accountToken])
                ->timeout($this->timeout)
                ->post(self::BASE_URL . '/qr', ['type' => 'qr']);

            $data = $response->json() ?? [];

            if (!$response->successful()) {
                Log::warning('Fonnte getQrCode gagal', ['status' => $response->status(), 'body' => $data]);
                return ['status' => false, 'reason' => $data['reason'] ?? 'Gagal menghubungi Fonnte'];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('Fonnte getQrCode exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'reason' => 'Tidak bisa menghubungi Fonnte'];
        }
    }

    /**
     * Cek status koneksi device saat ini (connect/disconnect).
     */
    public function getDeviceStatus(): array
    {
        if (!$this->isConfigured()) {
            return ['status' => false, 'device_status' => 'disconnect', 'reason' => 'Token Fonnte belum diset'];
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->token])
                ->timeout($this->timeout)
                ->post(self::BASE_URL . '/device');

            $data = $response->json() ?? [];

            if (!$response->successful()) {
                Log::warning('Fonnte getDeviceStatus gagal', ['status' => $response->status(), 'body' => $data]);
                return ['status' => false, 'device_status' => 'disconnect', 'reason' => $data['reason'] ?? 'Gagal menghubungi Fonnte'];
            }

            return $data;
        } catch (\Throwable $e) {
            Log::error('Fonnte getDeviceStatus exception', ['message' => $e->getMessage()]);
            return ['status' => false, 'device_status' => 'disconnect', 'reason' => 'Tidak bisa menghubungi Fonnte'];
        }
    }

    /**
     * Kirim pesan teks WhatsApp ke satu nomor. Return true kalau Fonnte
     * menerima permintaan (masuk antrean kirim), bukan berarti pesan sudah
     * benar-benar sampai di HP penerima.
     */
    public function sendMessage(string $target, string $message): bool
    {
        if (!$this->isConfigured()) {
            Log::warning('Fonnte sendMessage dilewati: token belum diset');
            return false;
        }

        try {
            $response = Http::withHeaders(['Authorization' => $this->token])
                ->timeout($this->timeout)
                ->asForm()
                ->post(self::BASE_URL . '/send', [
                    'target' => $target,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            $data = $response->json() ?? [];

            if (!$response->successful() || !($data['status'] ?? false)) {
                Log::warning('Fonnte sendMessage gagal', ['target' => $target, 'body' => $data]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('Fonnte sendMessage exception', ['target' => $target, 'message' => $e->getMessage()]);
            return false;
        }
    }
}
