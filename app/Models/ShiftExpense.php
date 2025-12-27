<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftExpense extends Model
{
    protected $fillable = [
        'shift_id',
        'order_id',
        'description',
        'amount',
        'category',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'order_id');
    }

    public function scopeOperational($query)
    {
        return $query->where('category', 'operational');
    }

    public function scopeRefunds($query)
    {
        return $query->where('category', 'refund');
    }

    public function isRefund(): bool
    {
        return $this->category === 'refund';
    }
}
