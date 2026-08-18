<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Komponen / item setengah jadi hasil repacking.
 *
 * Contoh: Bakso Kecil, Bakso Besar, Kuah, Sambal, Es Batu, dll.
 * Komponen digunakan dalam BOM produk dan stoknya dikurangi saat checkout.
 */
class Component extends Model
{
    // Tidak pakai SyncsToReport: raw unit_id tidak cocok dengan kolom 'unit' (string)
    // di DB report. Sync ditangani eksplisit lewat ReportSyncService::syncComponent().
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
        'stock'         => 'float',
        'minimum_stock' => 'float',
        'cost_price'    => 'float',
        'is_active'     => 'boolean',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function stockLogs(): HasMany
    {
        return $this->hasMany(ComponentStockLog::class)->latest();
    }

    public function productionOutputs(): HasMany
    {
        return $this->hasMany(ProductionOutput::class);
    }

    public function boms(): HasMany
    {
        return $this->hasMany(ProductBom::class);
    }

    /**
     * Modifier yang menggunakan komponen ini untuk pengurangan stok.
     */
    public function modifiers(): HasMany
    {
        return $this->hasMany(Modifier::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

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
     * Cek apakah stok komponen di bawah batas minimum.
     */
    public function isLowStock(): bool
    {
        return $this->stock <= $this->minimum_stock && $this->stock > 0;
    }

    /**
     * Cek apakah stok komponen habis.
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

    /**
     * Kurangi stok komponen. Harus dipanggil dalam DB::transaction().
     *
     * @throws \App\Exceptions\InsufficientStockException
     */
    public function decrementStock(float $qty, bool $throw = true): bool
    {
        $newStock = $this->stock - $qty;

        if ($newStock < 0) {
            if ($throw) {
                throw new \App\Exceptions\InsufficientStockException(
                    "Stok komponen '{$this->name}' tidak cukup "
                    . "(Dibutuhkan: {$qty} {$this->unit?->symbol}, "
                    . "Tersisa: {$this->stock} {$this->unit?->symbol})"
                );
            }
            return false;
        }

        $this->update(['stock' => $newStock]);
        return true;
    }

    /**
     * Tambah stok komponen.
     */
    public function incrementStock(float $qty): void
    {
        $this->increment('stock', $qty);
    }

    // -----------------------------------------------------------------
    // Static Query Methods
    // -----------------------------------------------------------------

    /**
     * Ambil semua komponen aktif untuk dropdown form repacking.
     */
    public static function getForRepacking(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->with('unit')
            ->orderBy('name')
            ->get();
    }

    /**
     * Paginated list untuk halaman admin komponen.
     */
    public static function getPaginatedForAdmin(
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return static::with('unit')
        ->when(
            $search,
            fn($q) => $q->where('name', 'like', "%{$search}%")
                       ->orWhere('code', 'like', "%{$search}%")
        )
        ->latest()
        ->paginate($perPage);
    }

    /**
     * Ambil komponen dengan lock untuk update stok (gunakan dalam transaksi DB).
     */
    public static function getForStockUpdate(int $id): ?self
    {
        return static::lockForUpdate()->find($id);
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getFormattedStockAttribute(): string
    {
        return number_format($this->stock, 2, ',', '.') . ' ' . $this->unit?->symbol;
    }
}
