<?php

namespace App\Services;

use App\Models\BlockedPhoneNumber;
use App\Models\SelfOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * PhoneBlacklistService
 *
 * Satu pintu untuk semua operasi blokir/unblokir nomor HP pelanggan, termasuk
 * deteksi & blokir otomatis saat nomor yang sama spam submit Self Order dalam
 * rentang waktu tertentu.
 */
class PhoneBlacklistService
{
    public function isBlocked(string $phone): bool
    {
        return BlockedPhoneNumber::isPhoneBlocked($phone);
    }

    /**
     * Blokir nomor HP secara manual (admin/kasir) atau otomatis (sistem anti-spam).
     */
    public function block(string $phone, ?string $reason = null, ?int $blockedBy = null, bool $auto = false): BlockedPhoneNumber
    {
        return BlockedPhoneNumber::updateOrCreate(
            ['phone' => $phone],
            [
                'is_blocked'   => true,
                'auto_blocked' => $auto,
                'reason'       => $reason,
                'blocked_by'   => $blockedBy,
                'blocked_at'   => now(),
                'unblocked_by' => null,
                'unblocked_at' => null,
            ]
        );
    }

    /**
     * Buka blokir nomor HP. Baris tetap disimpan (bukan dihapus) sebagai riwayat.
     */
    public function unblock(string $phone, int $unblockedBy): ?BlockedPhoneNumber
    {
        $entry = BlockedPhoneNumber::where('phone', $phone)->first();

        if (!$entry) {
            return null;
        }

        $entry->update([
            'is_blocked'   => false,
            'unblocked_by' => $unblockedBy,
            'unblocked_at' => now(),
        ]);

        return $entry;
    }

    /**
     * Dipanggil setelah sebuah Self Order berhasil dibuat. Menghitung jumlah
     * order dari nomor HP yang sama dalam jendela waktu tertentu (Setting
     * self_order.spam_window_minutes) — kalau melewati ambang batas
     * (self_order.spam_threshold), nomor diblokir otomatis.
     */
    public function registerSelfOrderAndAutoBlockIfSpam(string $phone): void
    {
        if ($this->isBlocked($phone)) {
            return;
        }

        $windowMinutes = (int) Setting::get('spam_window_minutes', 10, 'self_order');
        $threshold     = (int) Setting::get('spam_threshold', 5, 'self_order');

        $recentCount = SelfOrder::where('customer_phone', $phone)
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->count();

        if ($recentCount >= $threshold) {
            $this->block(
                phone: $phone,
                reason: "Otomatis: {$recentCount} pesanan dalam {$windowMinutes} menit terakhir.",
                blockedBy: null,
                auto: true,
            );

            Log::channel('self_order')->warning(
                "[AntiSpam] Nomor HP [{$phone}] diblokir otomatis: {$recentCount} order dalam {$windowMinutes} menit terakhir."
            );
        }
    }
}
