<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Traits\SyncsToReport;

use App\Enums\KitchenTarget;

class Category extends Model
{
    use SyncsToReport, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_active',
        'target_kitchen',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'target_kitchen' => KitchenTarget::class,
    ];

    protected static function boot(): void
    {
        parent::boot();
        
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function activeProducts(): HasMany
    {
        return $this->hasMany(Product::class)->where('is_active', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope untuk tampilan POS: hanya yang aktif, diurutkan berdasarkan sort_order.
     */
    public function scopeForPos($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope berdasarkan klasifikasi dapur.
     */
    public function scopeForKitchenTarget($query, $target)
    {
        $targetValue = $target instanceof KitchenTarget ? $target->value : $target;
        return $query->where('target_kitchen', $targetValue);
    }

    /**
     * Get maximum sort order value.
     */
    public static function getMaxSortOrder(): int
    {
        return static::max('sort_order') ?? 0;
    }

    /**
     * Check if a sort order exists.
     */
    public static function isSortOrderExists(int $sortOrder, ?int $exceptId = null): bool
    {
        return static::where('sort_order', $sortOrder)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }

    /**
     * Get paginated categories with product counts.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->withCount('products')
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    /**
     * Check if category exists by ID.
     */
    public static function existsById(int $id): bool
    {
        return static::where('id', $id)->exists();
    }

    /**
     * Check if category exists by slug.
     */
    public static function existsBySlug(string $slug): bool
    {
        return static::where('slug', $slug)->exists();
    }

    /**
     * Get all categories sorted by name.
     */
    public static function getAllSortedByName(): \Illuminate\Database\Eloquent\Collection
    {
        return static::orderBy('name')->get();
    }
}

