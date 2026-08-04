<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfOrderItemModifier extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'self_order_item_id',
        'modifier_id',
        'modifier_name',
        'price_adjustment',
        'quantity',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'quantity'         => 'integer',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function selfOrderItem(): BelongsTo
    {
        return $this->belongsTo(SelfOrderItem::class);
    }

    public function modifier(): BelongsTo
    {
        return $this->belongsTo(Modifier::class);
    }
}
