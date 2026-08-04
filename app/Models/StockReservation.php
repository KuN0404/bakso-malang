<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class StockReservation extends Model
{
    protected $fillable = [
        'reservable_type',
        'reservable_id',
        'item_type',
        'item_id',
        'quantity',
        'status',
        'expires_at',
        'released_at',
    ];

    protected $casts = [
        'quantity'    => 'float',
        'expires_at'  => 'datetime',
        'released_at' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'active')
            ->where('expires_at', '<=', now());
    }

    // -----------------------------------------------------------------
    // Static Query Methods
    // -----------------------------------------------------------------

    /**
     * Hitung total stok yang sedang direservasi untuk produk tertentu.
     * Digunakan untuk menghitung available_stock.
     */
    public static function getTotalActiveForProduct(int $productId): float
    {
        return (float) static::where('item_type', 'product')
            ->where('item_id', $productId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /**
     * Hitung total stok yang sedang direservasi untuk komponen tertentu.
     */
    public static function getTotalActiveForComponent(int $componentId): float
    {
        return (float) static::where('item_type', 'component')
            ->where('item_id', $componentId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->sum('quantity');
    }

    /**
     * Ambil semua reservation aktif yang sudah expire (untuk Job cleanup).
     */
    public static function getExpiredActive(): Collection
    {
        return static::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();
    }

    /**
     * Lepas semua reservation aktif untuk suatu order.
     * Dipanggil saat order dibatalkan/expired.
     */
    public static function releaseAllForOrder(string $reservableType, int $reservableId): int
    {
        return static::where('reservable_type', $reservableType)
            ->where('reservable_id', $reservableId)
            ->where('status', 'active')
            ->update([
                'status'      => 'released',
                'released_at' => now(),
            ]);
    }

    /**
     * Konversi semua reservation aktif ke 'converted' saat settlement.
     * Stok aktual sudah dikurangi → reservation tidak lagi block stok.
     */
    public static function convertAllForOrder(string $reservableType, int $reservableId): int
    {
        return static::where('reservable_type', $reservableType)
            ->where('reservable_id', $reservableId)
            ->where('status', 'active')
            ->update([
                'status'      => 'converted',
                'released_at' => now(),
            ]);
    }

    /**
     * Ambil semua reservation aktif untuk order dengan FOR UPDATE lock.
     * Dipanggil di dalam DB::transaction().
     */
    public static function getActiveForOrderLocked(string $reservableType, int $reservableId): Collection
    {
        return static::where('reservable_type', $reservableType)
            ->where('reservable_id', $reservableId)
            ->where('status', 'active')
            ->lockForUpdate()
            ->get();
    }
}
