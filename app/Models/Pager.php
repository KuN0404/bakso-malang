<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\SyncsToReport;

class Pager extends Model
{
    use SyncsToReport, SoftDeletes;

    protected $fillable = [
        'number',
        'docking_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function selfOrders(): HasMany
    {
        return $this->hasMany(SelfOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function getPaginated(string $search = '', int $perPage = 10): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return static::query()
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhere('docking_number', 'like', "%{$search}%");
            }))
            ->orderBy('number')
            ->paginate($perPage);
    }
}
