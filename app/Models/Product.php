<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Traits\SyncsToReport;

class Product extends Model
{
    use SoftDeletes, \Spatie\Activitylog\Traits\LogsActivity, SyncsToReport;
    
    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'cost_price',
        'image',
        'is_active',
        'is_featured',
        'stock',
        'track_stock',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->logAll()
            ->logExcept(['stock']) // Handle stock via StockLog
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }



    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'track_stock' => 'boolean',
        'stock' => 'integer',
    ];

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate slug on save
        static::saving(function ($product) {
            if (empty($product->slug) || $product->isDirty('name')) {
                $product->slug = \Illuminate\Support\Str::slug($product->name);
                
                // Ensure unique slug
                $originalSlug = $product->slug;
                $count = 1;
                while (static::where('slug', $product->slug)->where('id', '!=', $product->id)->exists()) {
                    $product->slug = $originalSlug . '-' . $count++;
                }
            }
        });

        // Delete image when product is force deleted
        static::forceDeleting(function ($product) {
            $product->deleteImage();
        });
    }

    /**
     * Delete the product image from storage
     */
    public function deleteImage(): bool
    {
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return Storage::disk('public')->delete($this->image);
        }
        return false;
    }

    /**
     * Get the full image URL
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return Storage::disk('public')->url($this->image);
        }
        return null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function modifierGroups(): BelongsToMany
    {
        return $this->belongsToMany(ModifierGroup::class, 'product_modifier_group');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_stock', false)
              ->orWhere('stock', '>', 0);
        });
    }

    /**
     * Get product with active modifiers for POS cart additions.
     */
    public static function getForPos(int $id): ?self
    {
        return static::with('modifierGroups.activeModifiers')->find($id);
    }

    public function getFormattedPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function stockLogs()
    {
        return $this->hasMany(StockLog::class)->latest();
    }

    /**
     * Get paginated stock logs for the product.
     */
    public function getPaginatedStockLogs(int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->stockLogs()->with('user')->paginate($perPage);
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->track_stock && $this->stock <= 0) {
            return false;
        }

        return true;
    }

    public function decrementStock(int $quantity = 1): bool
    {
        if (!$this->track_stock) {
            return true;
        }

        if ($this->stock >= $quantity) {
            $this->decrement('stock', $quantity);
            return true;
        }

        return false;
    }

    /**
     * Scope untuk tampilan POS: ambil produk aktif dengan filter kategori dan pencarian.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int|null  $categoryId
     * @param  string|null  $search
     */
    public function scopeForPosDisplay($query, ?int $categoryId = null, ?string $search = null)
    {
        $query->where('is_active', true)
            ->with(['category', 'modifierGroups.activeModifiers']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Get paginated products for admin view with search and category filtering.
     */
    public static function getPaginatedForAdmin(?string $search = null, ?string $filterCategory = null, int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::with('category')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
            ->when($filterCategory, fn($q) => $q->whereHas('category', fn($c) => $c->where('slug', $filterCategory)))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get products by IDs with pessimistic locking for order processing.
     */
    public static function getForCheckout(array $productIds): \Illuminate\Database\Eloquent\Collection
    {
        return static::whereIn('id', $productIds)->lockForUpdate()->get();
    }

    /**
     * Get product with modifier groups for editing.
     */
    public static function getWithModifierGroups(int $id): self
    {
        return static::with('modifierGroups')->findOrFail($id);
    }
}
