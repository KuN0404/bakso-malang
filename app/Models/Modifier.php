<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\SyncsToReport;

class Modifier extends Model
{
    use SyncsToReport, SoftDeletes;

    protected $fillable = [
        'modifier_group_id',
        'name',
        'price_adjustment',
        'is_active',
        'sort_order',
        'component_id', // Jika diisi, checkout akan mengurangi stok komponen ini
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function modifierGroup(): BelongsTo
    {
        return $this->belongsTo(ModifierGroup::class);
    }

    /**
     * Komponen yang stoknya dikurangi saat modifier ini dipilih di POS.
     * Nullable — jika null, modifier tidak mengurangi stok komponen.
     */
    public function component(): BelongsTo
    {
        return $this->belongsTo(Component::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Cek apakah modifier ini dikaitkan ke stok komponen.
     */
    public function hasComponentStock(): bool
    {
        return !is_null($this->component_id);
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->price_adjustment == 0) {
            return 'Gratis';
        }

        $prefix = $this->price_adjustment > 0 ? '+' : '';
        return $prefix . 'Rp ' . number_format($this->price_adjustment, 0, ',', '.');
    }

    /**
     * Get all modifiers for a specific modifier group.
     */
    public static function getByGroup(int $groupId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('modifier_group_id', $groupId)->get();
    }
}
