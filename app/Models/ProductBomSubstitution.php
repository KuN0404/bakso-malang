<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aturan substitusi untuk satu baris BOM.
 *
 * Contoh record:
 *   product_bom_id=5 (Bakso Urat membutuhkan Bakso Kecil × 3)
 *   component_id=2 (Bakso Besar Urat), quantity=2
 *
 * Artinya: 3 Bakso Kecil pada resep Bakso Urat boleh diganti 2 Bakso Besar Urat.
 * quantity menggantikan SELURUH qty baris BOM, per 1 unit produk.
 */
class ProductBomSubstitution extends Model
{
    protected $fillable = [
        'product_bom_id',
        'component_id',
        'quantity',
        'label',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'quantity'   => 'float',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function productBom(): BelongsTo
    {
        return $this->belongsTo(ProductBom::class, 'product_bom_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Label untuk tombol di POS, mis. "Gunakan 2 Bakso Besar Urat".
     */
    public function getDisplayLabelAttribute(): string
    {
        if ($this->label) {
            return $this->label;
        }

        $qty  = rtrim(rtrim(number_format($this->quantity, 3, ',', '.'), '0'), ',');
        $unit = $this->component?->unit?->symbol;

        return trim("Gunakan {$qty} {$unit} {$this->component?->name}");
    }
}
