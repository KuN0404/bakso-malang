<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'unit',
        'stock',
        'minimum_stock',
        'cost_price',
        'note',
        'is_active',
    ];

    protected $casts = [
        'stock' => 'float',
        'minimum_stock' => 'float',
        'cost_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function stockLogs()
    {
        return $this->hasMany(IngredientStockLog::class);
    }
}
