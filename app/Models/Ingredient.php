<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\SyncsToReport;

class Ingredient extends Model
{
    use SoftDeletes, SyncsToReport;

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

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeLowStock($query)
    {
        return $query->where('stock', '<=', \Illuminate\Support\Facades\DB::raw('minimum_stock'));
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    /**
     * Cek apakah stok bahan baku di bawah batas minimum.
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock && $this->stock > 0;
    }

    /**
     * Cek apakah stok bahan baku habis.
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Status label untuk UI.
     * Return: 'ok', 'low', 'out'
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) return 'out';
        if ($this->isLowStock())   return 'low';
        return 'ok';
    }
}
