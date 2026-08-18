<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingredient extends Model
{
    // Tidak pakai SyncsToReport: raw unit_id tidak cocok dengan kolom 'unit' (string)
    // di DB report. Sync ditangani eksplisit lewat ReportSyncService::syncIngredient().
    use SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'unit_id',
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // -----------------------------------------------------------------
    // Queries
    // -----------------------------------------------------------------

    /**
     * Untuk dropdown pilihan bahan baku (mis. form Pembelian Stok) — eager
     * load unit supaya tidak N+1 saat menampilkan simbol satuan per baris.
     */
    public static function getAllActiveWithUnit(): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()->with('unit')->orderBy('name')->get();
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
