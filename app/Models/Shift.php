<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'user_id',
        'started_at',
        'ended_at',
        'opening_cash',
        'expected_cash',
        'actual_cash',
        'cash_difference',
        'status',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'opening_cash' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'cash_difference' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function completedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class)->where('status', 'completed');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(ShiftExpense::class);
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function calculateExpectedCash(): float
    {
        $cashSales = $this->completedTransactions()
            ->where('payment_method', 'cash')
            ->sum('total');

        $totalExpenses = $this->expenses()->sum('amount');

        return $this->opening_cash + $cashSales - $totalExpenses;
    }

    public function close(float $actualCash, ?string $notes = null): bool
    {
        $expectedCash = $this->calculateExpectedCash();
        $difference = $actualCash - $expectedCash;

        return $this->update([
            'ended_at' => now(),
            'expected_cash' => $expectedCash,
            'actual_cash' => $actualCash,
            'cash_difference' => $difference,
            'status' => 'closed',
            'notes' => $notes,
        ]);
    }

    public function getTotalSalesAttribute(): float
    {
        return $this->completedTransactions()->sum('total');
    }

    public function getCashSalesAttribute(): float
    {
        return $this->completedTransactions()
            ->where('payment_method', 'cash')
            ->sum('total');
    }

    public function getNonCashSalesAttribute(): float
    {
        return $this->completedTransactions()
            ->where('payment_method', '!=', 'cash')
            ->sum('total');
    }

    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()->sum('amount');
    }
}
