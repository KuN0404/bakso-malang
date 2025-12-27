<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModifierGroup extends Model
{
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
}
