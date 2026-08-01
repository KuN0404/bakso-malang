<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionOutput extends Model
{
    protected $fillable = [
        'production_id',
        'product_id',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'subtotal' => 'float',
    ];

    public function production()
    {
        return $this->belongsTo(Production::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
