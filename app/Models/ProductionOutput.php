<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOutput extends Model
{
    protected $fillable = [
        'production_id',
        'component_id', // Output ke komponen (repacking baru)
        'product_id',   // Output ke produk (backward compat — data lama)
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'quantity'  => 'float',
        'unit_cost' => 'float',
        'subtotal'  => 'float',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /**
     * Relasi ke komponen (output repacking baru).
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Relasi ke produk (backward compat untuk data lama).
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    /**
     * Nama output — ambil dari komponen jika ada, fallback ke produk.
     */
    public function getOutputName(): string
    {
        return $this->component?->name ?? $this->product?->name ?? 'Unknown';
    }

    /**
     * Satuan output.
     */
    public function getOutputUnit(): string
    {
        return $this->component?->unit?->symbol ?? 'pcs';
    }
}
