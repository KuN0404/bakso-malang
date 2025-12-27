<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSource extends Model
{
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
}
