<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit trail pergerakan stok komponen.
 *
 * Types:
 * - production_add  : Penambahan stok dari repacking/produksi
 * - bom_deduct      : Pengurangan stok dari checkout via BOM produk
 * - modifier_deduct : Pengurangan stok dari checkout via modifier
 * - return_add      : Pengembalian stok dari retur transaksi
 * - adjustment      : Penyesuaian manual oleh admin
 * - initial         : Stok awal saat komponen dibuat
 */
class ComponentStockLog extends Model
{
    protected static function booted(): void
    {
        static::created(function ($log) {
            app(\App\Services\ReportSyncService::class)->syncComponentStockLog($log);
        });
    }

    protected $fillable = [
        'component_id',
        'user_id',
        'type',
        'amount',
        'final_stock',
        'note',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'amount'      => 'float',
        'final_stock' => 'float',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // -----------------------------------------------------------------
    // Factory Method
    // -----------------------------------------------------------------

    /**
     * Catat pergerakan stok komponen secara standar.
     * Selalu gunakan method ini — jangan buat record langsung.
     */
    public static function record(
        int $componentId,
        ?int $userId,
        string $type,
        float $amount,
        float $finalStock,
        ?string $note = null,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): self {
        return static::create([
            'component_id'   => $componentId,
            'user_id'        => $userId,
            'type'           => $type,
            'amount'         => $amount,
            'final_stock'    => $finalStock,
            'note'           => $note,
            'reference_id'   => $referenceId,
            'reference_type' => $referenceType,
        ]);
    }
}
