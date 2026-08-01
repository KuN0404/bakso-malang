<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\SyncsToReport;

class PaymentSource extends Model
{
    use SyncsToReport;

    protected $fillable = [
        'name',
        'type',
        'account_number',
        'account_name',
        'icon',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    /**
     * Get the default cash payment source.
     */
    public static function getDefaultCash(): ?self
    {
        return static::active()->where('type', 'cash')->first();
    }

    /**
     * Get maximum sort order value.
     */
    public static function getMaxSortOrder(): int
    {
        return static::max('sort_order') ?? 0;
    }

    /**
     * Get paginated payment sources.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }
}
