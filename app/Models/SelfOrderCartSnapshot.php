<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfOrderCartSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'midtrans_order_id',
        'self_order_id',
        'cart_data',
        'expires_at',
        'created_at',
    ];

    protected $casts = [
        'cart_data'  => 'array',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function selfOrder(): BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    // -----------------------------------------------------------------
    // Static Methods
    // -----------------------------------------------------------------

    /**
     * Ambil cart data berdasarkan midtrans_order_id.
     * Digunakan oleh webhook handler.
     */
    public static function getByOrderId(string $midtransOrderId): ?self
    {
        return static::where('midtrans_order_id', $midtransOrderId)->first();
    }

    /**
     * Buat snapshot baru.
     */
    public static function createSnapshot(
        string $midtransOrderId,
        int $selfOrderId,
        array $cartData,
        \Carbon\Carbon $expiresAt
    ): self {
        return static::create([
            'midtrans_order_id' => $midtransOrderId,
            'self_order_id'     => $selfOrderId,
            'cart_data'         => $cartData,
            'expires_at'        => $expiresAt,
            'created_at'        => now(),
        ]);
    }

    /**
     * Hapus snapshot yang sudah tidak diperlukan.
     */
    public static function cleanOld(int $daysOld = 7): int
    {
        return static::where('expires_at', '<', now()->subDays($daysOld))->delete();
    }
}
