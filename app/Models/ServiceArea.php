<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceArea extends Model
{
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
}
