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
        return $this->belongsTo(User::class, 'blocked_by');
    }

    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    /**
     * Cek cepat apakah sebuah nomor HP sedang diblokir. Dipakai di jalur
     * hot-path (submit Self Order), jadi query tunggal indexed lookup.
     */
    public static function isPhoneBlocked(string $phone): bool
    {
        return static::where('phone', $phone)->where('is_blocked', true)->exists();
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
