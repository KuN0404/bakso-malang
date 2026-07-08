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
        'expected_non_cash',
        'actual_non_cash',
        'non_cash_difference',
        'status',
        'notes',
        'close_notes',
    ];

    protected $casts = [
        'started_at'          => 'datetime',
        'ended_at'            => 'datetime',
        'opening_cash'        => 'decimal:2',
        'expected_cash'       => 'decimal:2',
        'actual_cash'         => 'decimal:2',
        'cash_difference'     => 'decimal:2',
        'expected_non_cash'   => 'decimal:2',
        'actual_non_cash'     => 'decimal:2',
        'non_cash_difference' => 'decimal:2',
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

    /**
     * Expected cash in the drawer:
     * Opening Cash + All CASH Sales - Expenses (operational/refunds)
     */
    public function calculateExpectedCash(): float
    {
        $cashSales = $this->completedTransactions()
            ->where('payment_method', 'cash')
            ->sum('total');

        $totalExpenses = $this->expenses()->sum('amount');

        return (float) $this->opening_cash + $cashSales - $totalExpenses;
    }

    /**
     * Expected non-cash total from system records (QRIS/Transfer/EDC).
     * Does NOT include expenses as those are cash-based.
     */
    public function calculateExpectedNonCash(): float
    {
        return (float) $this->completedTransactions()
            ->where('payment_method', '!=', 'cash')
            ->sum('total');
    }

    /**
     * Close the shift with separate cash and non-cash verification.
     *
     * @param  float       $actualCash    Physical cash in drawer counted by cashier
     * @param  float       $actualNonCash Non-cash (QRIS/Transfer) verified from bank statement
     * @param  string|null $notes
     */
    public function close(float $actualCash, float $actualNonCash, ?string $notes = null): bool
    {
        $expectedCash    = $this->calculateExpectedCash();
        $expectedNonCash = $this->calculateExpectedNonCash();

        return $this->update([
            'ended_at'            => now(),
            'expected_cash'       => $expectedCash,
            'actual_cash'         => $actualCash,
            'cash_difference'     => $actualCash - $expectedCash,
            'expected_non_cash'   => $expectedNonCash,
            'actual_non_cash'     => $actualNonCash,
            'non_cash_difference' => $actualNonCash - $expectedNonCash,
            'status'              => 'closed',
            'close_notes'         => $notes,
        ]);
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

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
