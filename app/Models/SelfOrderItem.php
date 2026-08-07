<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SelfOrderItem extends Model
{
    protected $fillable = [
        'self_order_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'modifier_total',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'unit_price'     => 'decimal:2',
        'modifier_total' => 'decimal:2',
        'subtotal'       => 'decimal:2',
        'quantity'       => 'integer',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function selfOrder(): BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modifiers(): HasMany
    {
        return $this->hasMany(SelfOrderItemModifier::class)
            ->join('modifiers', 'self_order_item_modifiers.modifier_id', '=', 'modifiers.id')
            ->orderBy('modifiers.modifier_group_id')
            ->orderBy('modifiers.sort_order')
            ->select('self_order_item_modifiers.*');
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    /**
     * Hitung subtotal berdasarkan snapshot harga.
     */
    public function calculateSubtotal(): float
    {
        return ($this->unit_price + $this->modifier_total) * $this->quantity;
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getModifierNamesAttribute(): string
    {
        return $this->modifiers->map(function ($m) {
            $qty = ($m->quantity ?? 1) > 1 ? " ×{$m->quantity}" : '';
            return $m->modifier_name . $qty;
        })->implode(', ');
    }
}
