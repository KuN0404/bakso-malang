<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'item_type',
        'ingredient_id',
        'product_id',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'subtotal' => 'float',
    ];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
