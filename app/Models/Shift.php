<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

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

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    // -----------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    // -----------------------------------------------------------------
    // Static Query Methods (dipanggil dari Livewire/Controller)
    // -----------------------------------------------------------------

    protected static ?self $cachedActiveShift = null;
    protected static ?int $cachedActiveShiftUserId = null;
    protected static bool $hasCachedActiveShift = false;

    protected static ?self $cachedTodayShift = null;
    protected static ?int $cachedTodayShiftUserId = null;
    protected static bool $hasCachedTodayShift = false;

    /**
     * Bersihkan static cache memori
     */
    public static function clearStaticCache(): void
    {
        static::$cachedActiveShift = null;
        static::$cachedActiveShiftUserId = null;
        static::$hasCachedActiveShift = false;
        static::$cachedTodayShift = null;
        static::$cachedTodayShiftUserId = null;
        static::$hasCachedTodayShift = false;
    }

    /**
     * Ambil shift aktif (status open) milik kasir tertentu.
     */
    public static function getActiveShift(int $userId): ?self
    {
        if (static::$cachedActiveShiftUserId === $userId && static::$hasCachedActiveShift) {
            return static::$cachedActiveShift;
        }

        static::$cachedActiveShiftUserId = $userId;
        static::$cachedActiveShift = static::where('user_id', $userId)
            ->where('status', 'open')
            ->first();
        static::$hasCachedActiveShift = true;

        return static::$cachedActiveShift;
    }

    /**
     * Cek apakah ada shift hari-hari sebelumnya yang belum ditutup.
     */
    public static function getUnclosedPreviousShift(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('status', 'open')
            ->whereDate('started_at', '<', today())
            ->with('transactions')
            ->first();
    }

    /**
     * Ambil shift aktif hari ini atau shift terakhir (untuk tampilan POS).
     * Prioritas: shift open > shift terbaru (walau sudah closed).
     */
    public static function getTodayShift(int $userId): ?self
    {
        if (static::$cachedTodayShiftUserId === $userId && static::$hasCachedTodayShift) {
            return static::$cachedTodayShift;
        }

        $openShift = static::where('user_id', $userId)
            ->where('status', 'open')
            ->first();

        if ($openShift) {
            static::$cachedTodayShiftUserId = $userId;
            static::$cachedTodayShift = $openShift;
            static::$hasCachedTodayShift = true;
            return $openShift;
        }

        $latestShift = static::where('user_id', $userId)
            ->latest()
            ->first();

        static::$cachedTodayShiftUserId = $userId;
        static::$cachedTodayShift = $latestShift;
        static::$hasCachedTodayShift = true;
        return $latestShift;
    }

    /**
     * Ambil atau buat shift hari ini untuk kasir tertentu.
     * Hanya mengambil shift yang OPEN — tidak membuat ulang jika sudah closed.
     */
    public static function getOrCreateTodayShift(int $userId): self
    {
        $shift = static::where('user_id', $userId)
            ->whereDate('started_at', today())
            ->where('status', 'open')
            ->first();

        if (!$shift) {
            $shift = static::create([
                'user_id'    => $userId,
                'started_at' => now(),
                'opening_cash' => 0,
                'status'     => 'open',
            ]);
            static::clearStaticCache();
        }

        return $shift;
    }

    /**
     * Query shift untuk halaman ShiftReport dengan filter periode dan kasir.
     */
    public static function getShiftReport(
        Carbon $start,
        Carbon $end,
        ?int $userId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return static::with('user')
            ->withSum(['transactions' => fn($q) => $q->where('status', 'completed')], 'total')
            ->withSum('expenses', 'amount')
            ->whereBetween('started_at', [$start, $end])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('started_at')
            ->paginate($perPage);
    }

    /**
     * Summary stats untuk header ShiftReport (total penjualan, pengeluaran, selisih).
     */
    public static function getShiftSummary(Carbon $start, Carbon $end, ?int $userId = null): array
    {
        $baseQuery = static::whereBetween('started_at', [$start, $end])
            ->when($userId, fn($q) => $q->where('user_id', $userId));

        $totalSales = (clone $baseQuery)
            ->withSum(['transactions' => fn($q) => $q->where('status', 'completed')], 'total')
            ->get()
            ->sum('transactions_sum_total');

        $totalExpenses = (clone $baseQuery)
            ->withSum('expenses', 'amount')
            ->get()
            ->sum('expenses_sum_amount');

        $totalDifference = (clone $baseQuery)->sum('cash_difference');

        return [
            'total_sales'      => $totalSales,
            'total_expenses'   => $totalExpenses,
            'total_difference' => $totalDifference,
        ];
    }

    /**
     * Query data shift untuk Export.
     */
    public static function getExportQuery(Carbon $start, Carbon $end, ?int $userId = null)
    {
        return static::query()
            ->with(['user', 'transactions', 'expenses'])
            ->whereBetween('started_at', [$start, $end])
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('started_at');
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

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

        $updated = $this->update([
            'ended_at'            => now(),
            'expected_cash'       => $expectedCash,
            'actual_cash'         => $actualCash,
            'cash_difference'     => $actualCash - $expectedCash,
            'expected_non_cash'   => $expectedNonCash,
            'actual_non_cash'     => $actualNonCash,
            'non_cash_difference' => $actualNonCash - $expectedNonCash,
            'status'              => 'closed',
            'notes'               => $notes,
        ]);

        if ($updated) {
            static::clearStaticCache();
        }

        return $updated;
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

    /**
     * Get all currently open shifts (for Kitchen Display shift selection).
     */
    public static function getOpenShifts(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('status', 'open')
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get today's shifts with optional user filter.
     */
    public static function getTodayShiftsForUser(?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'transactions', 'expenses'])
            ->whereDate('started_at', today())
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->latest('started_at')
            ->get();
    }

    /**
     * Get shift with loaded relationships for detail view.
     */
    public static function getForDetail(int $id): self
    {
        return static::with(['user', 'transactions.paymentSource', 'expenses'])->findOrFail($id);
    }

    /**
     * Get shifts for print table report.
     */
    public static function getForPrintTable(Carbon $start, Carbon $end, ?int $cashierId = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'transactions', 'expenses'])
            ->whereBetween('started_at', [$start, $end])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('started_at')
            ->get();
    }

    /**
     * Load relationships for print detail.
     */
    public function loadForPrintDetail(): self
    {
        return $this->load(['user', 'transactions.paymentSource', 'expenses']);
    }

    /**
     * Load relationships for print custom shift detail.
     */
    public function loadForPrintCustom(): self
    {
        return $this->load(['user', 'expenses', 'transactions' => function($q) {
             $q->where('status', 'completed')->orderBy('created_at');
        }]);
    }
}


