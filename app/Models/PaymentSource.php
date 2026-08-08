<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\SyncsToReport;

class PaymentSource extends Model
{
    use SyncsToReport, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'account_number',
        'account_name',
        'icon',
        'is_active_pos',
        'is_active_self_order',
        'sort_order',
    ];

    protected $casts = [
        'is_active_pos'        => 'boolean',
        'is_active_self_order' => 'boolean',
        'sort_order'           => 'integer',
    ];

    // -----------------------------------------------------------------
    // Scopes (Encapsulated Queries)
    // -----------------------------------------------------------------

    public function scopeActiveForPos($query)
    {
        return $query->where('is_active_pos', true);
    }

    public function scopeActiveForSelfOrder($query)
    {
        return $query->where('is_active_self_order', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // -----------------------------------------------------------------
    // Business & Helper Methods
    // -----------------------------------------------------------------

    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    /**
     * Get active payment sources for POS Checkout.
     */
    public static function getForPos(): \Illuminate\Database\Eloquent\Collection
    {
        return static::activeForPos()->ordered()->get();
    }

    /**
     * Get active payment sources for Self Order.
     */
    public static function getForSelfOrder(): \Illuminate\Database\Eloquent\Collection
    {
        return static::activeForSelfOrder()->ordered()->get();
    }

    /**
     * Check if QRIS is active for Self Order.
     */
    public static function isQrisActiveForSelfOrder(): bool
    {
        return static::activeForSelfOrder()->where('type', 'qris')->exists();
    }

    /**
     * Check if Cash (Bayar di Kasir) is active for Self Order.
     */
    public static function isCashActiveForSelfOrder(): bool
    {
        return static::activeForSelfOrder()->where('type', 'cash')->exists();
    }

    /**
     * Get the default cash payment source for POS.
     */
    public static function getDefaultCash(): ?self
    {
        return static::activeForPos()->where('type', 'cash')->first();
    }

    /**
     * Get maximum sort order value.
     */
    public static function getMaxSortOrder(): int
    {
        return static::max('sort_order') ?? 0;
    }

    /**
     * Get paginated payment sources for admin management.
     */
    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('sort_order')
            ->paginate($perPage);
    }
}
