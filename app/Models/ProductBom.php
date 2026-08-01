<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
