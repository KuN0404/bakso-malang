<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bill of Materials — satu baris komposisi komponen untuk sebuah produk.
 *
 * Contoh record:
 *   product_id=1 (Bakso Biasa), component_id=1 (Bakso Kecil), quantity=3
 *
 * Artinya: setiap 1× Bakso Biasa yang terjual = 3 Bakso Kecil dikurangi dari stok.
 */
class ProductBom extends Model
{
    protected $table = 'product_bom';

    protected $fillable = [
        'product_id',
        'component_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    /**
     * Aturan pengganti untuk baris BOM ini (dipakai POS saat komponen normal kurang).
     */
    public function substitutions(): HasMany
    {
        return $this->hasMany(ProductBomSubstitution::class, 'product_bom_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function activeSubstitutions(): HasMany
    {
        return $this->substitutions()->where('is_active', true);
    }
}
