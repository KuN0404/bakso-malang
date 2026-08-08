<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Pagination\LengthAwarePaginator;

class BlockedPhoneNumber extends Model
{
    protected $fillable = [
        'phone',
        'is_blocked',
        'auto_blocked',
        'reason',
        'blocked_by',
        'blocked_at',
        'unblocked_by',
        'unblocked_at',
    ];

    protected $casts = [
        'is_blocked'   => 'boolean',
        'auto_blocked' => 'boolean',
        'blocked_at'   => 'datetime',
        'unblocked_at' => 'datetime',
    ];

    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by')->withTrashed();
    }

    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by')->withTrashed();
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Normalisasi nomor HP ke format kanonik 62xxxxxxxxxxx (tanpa +/spasi/strip,
     * prefix 0 diganti 62). Nomor HP bisa masuk ke aplikasi ini dalam 3 bentuk
     * berbeda tergantung siapa yang input (customer Self Order: +62/62/0, admin
     * form blokir: bebas, kasir POS: bebas) — tanpa disamakan dulu, exact-string
     * match di isPhoneBlocked() akan gagal mendeteksi nomor fisik yang sama.
     */
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Cek cepat apakah sebuah nomor HP sedang diblokir. Dipakai di jalur
     * hot-path (submit Self Order), jadi query tunggal indexed lookup.
     */
    public static function isPhoneBlocked(string $phone): bool
    {
        return static::where('phone', static::normalizePhone($phone))->where('is_blocked', true)->exists();
    }

    /**
     * Paginated list untuk halaman admin, dengan pencarian nomor/alasan.
     */
    public static function getPaginated(string $search = '', int $perPage = 15): LengthAwarePaginator
    {
        return static::query()
            ->with(['blockedBy', 'unblockedBy'])
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            }))
            ->orderByDesc('blocked_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }
}
