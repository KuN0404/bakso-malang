<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\SyncsToReport;

class ServiceArea extends Model
{
    use SyncsToReport, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'type',
        'capacity',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    // Type labels for display
    public const TYPE_LABELS = [
        'table' => 'Meja',
        'room' => 'Ruangan',
        'zone' => 'Zona',
        'other' => 'Lainnya',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope: Active only
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope: By type
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Get type label
    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? $this->type;
    }

    /**
     * Get maximum sort order value.
     */
    public static function getMaxSortOrder(): int
    {
        return static::max('sort_order') ?? 0;
    }

    /**
     * Get paginated service areas with transaction counts.
     */
    public static function getPaginated(string $search = '', string $filterType = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($filterType, fn($q) => $q->where('type', $filterType))
            ->withCount('transactions')
            ->orderBy('sort_order')
            ->paginate($perPage);
    }

    /**
     * Check if area has any completed transactions.
     */
    public function hasCompletedTransactions(): bool
    {
        return $this->transactions()->where('status', 'completed')->exists();
    }
}
