<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\SyncsToReport;

class ModifierGroup extends Model
{
    use SyncsToReport;

    protected $fillable = [
        'name',
        'selection_type',
        'is_required',
        'min_selections',
        'max_selections',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'min_selections' => 'integer',
        'max_selections' => 'integer',
    ];

    public function modifiers(): HasMany
    {
        return $this->hasMany(Modifier::class)->orderBy('sort_order');
    }

    public function activeModifiers(): HasMany
    {
        return $this->hasMany(Modifier::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_modifier_group');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isSingleSelection(): bool
    {
        return $this->selection_type === 'single';
    }

    public function isMultipleSelection(): bool
    {
        return $this->selection_type === 'multiple';
    }

    /**
     * Check if a modifier group exists by ID.
     */
    public static function existsById(int $id): bool
    {
        return static::where('id', $id)->exists();
    }

    /**
     * Get paginated modifier groups.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::withCount('modifiers')
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }
}
