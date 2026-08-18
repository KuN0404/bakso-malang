<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'address',
        'note',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get paginated suppliers, dengan pencarian nama/telepon.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public static function existsById(int $id): bool
    {
        return static::where('id', $id)->exists();
    }

    /**
     * Untuk dropdown pilihan supplier di form Pembelian Stok.
     */
    public static function getAllActiveSortedByName(): Collection
    {
        return static::active()->orderBy('name')->get();
    }
}
