<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'shift_id',
        'payment_source_id',
        'payment_transaction_id',
        'service_area_id',
        'pager_id',
        'self_order_id',
        'invoice_number',
        'receipt_token',
        'queue_number',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'total',
        'paid_amount',
        'change_amount',
        'payment_method',
        'payment_gateway_status',
        'source',
        'status',
        'cancelled_reason',
        'cancelled_by',
        'cancelled_at',
        'customer_name',
        'customer_phone',
        'customer_email',
        'order_type',
        'notes',
        'print_count',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'paid_amount'     => 'decimal:2',
        'change_amount'   => 'decimal:2',
        'queue_number'    => 'integer',
        'print_count'     => 'integer',
        'cancelled_at'    => 'datetime',
    ];

    // Eager load these relations by default for performance
    protected $with = ['details'];

    protected static function boot(): void
    {
        parent::boot();

        // Token acak untuk link struk publik (/receipt/{token}) — dibuat sekali
        // saat transaksi dibuat, terpisah dari invoice_number yang sekuensial
        // (dan karena itu bisa ditebak).
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->receipt_token)) {
                $transaction->receipt_token = (string) Str::uuid();
            }
        });
    }

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    public function paymentSource(): BelongsTo
    {
        return $this->belongsTo(PaymentSource::class);
    }

    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    public function pager(): BelongsTo
    {
        return $this->belongsTo(Pager::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by')->withTrashed();
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransactionDetail::class);
    }

    public function paymentTransaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class);
    }

    public function selfOrder(): BelongsTo
    {
        return $this->belongsTo(SelfOrder::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope filter untuk daftar riwayat transaksi (TransactionHistory Livewire).
     * Mendukung filter berdasarkan periode, pencarian invoice, dan kasir.
     */
    public function scopeFilter($query, Carbon $start, Carbon $end, ?string $search = null, ?int $cashierId = null, ?string $orderType = null)
    {
        return $query
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->when($orderType, fn($q) => $q->where('order_type', $orderType));
    }

    // -----------------------------------------------------------------
    // Static Query Methods (dipanggil dari Livewire/Controller)
    // -----------------------------------------------------------------

    /**
     * Summary statistik transaksi (total count + total revenue + breakdown per tipe order) untuk suatu periode.
     */
    public static function getSummaryStats(Carbon $start, Carbon $end, ?int $cashierId = null, ?string $orderType = null): array
    {
        $base = static::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->when($orderType, fn($q) => $q->where('order_type', $orderType));

        $dineInQuery = (clone $base)->where('order_type', 'dine_in');
        $takeAwayQuery = (clone $base)->where('order_type', 'take_away');

        return [
            'total_transactions' => (clone $base)->count(),
            'total_revenue'      => (clone $base)->sum('total'),
            'dine_in_count'      => (clone $dineInQuery)->count(),
            'dine_in_revenue'    => (clone $dineInQuery)->sum('total'),
            'take_away_count'    => (clone $takeAwayQuery)->count(),
            'take_away_revenue'  => (clone $takeAwayQuery)->sum('total'),
        ];
    }

    /**
     * Laporan penjualan per kategori (SalesReport tab categories).
     */
    public static function getSalesByCategoryReport(Carbon $start, Carbon $end, int $perPage = 10): LengthAwarePaginator
    {
        return TransactionDetail::query()
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('
                categories.id as category_id,
                categories.name as category_name,
                SUM(transaction_details.subtotal) as total_sales,
                SUM(transaction_details.quantity) as total_quantity,
                COUNT(DISTINCT transactions.id) as transaction_count
            ')
            ->orderByDesc('total_sales')
            ->paginate($perPage);
    }

    /**
     * Laporan penjualan per metode pembayaran (SalesReport tab payments).
     */
    public static function getSalesByPaymentReport(Carbon $start, Carbon $end, int $perPage = 10): LengthAwarePaginator
    {
        return static::query()
            ->leftJoin('payment_sources', 'transactions.payment_source_id', '=', 'payment_sources.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('transactions.payment_method', 'payment_sources.name')
            ->selectRaw('
                CASE
                    WHEN transactions.payment_method = "cash" THEN "Tunai"
                    ELSE COALESCE(payment_sources.name, transactions.payment_method)
                END as payment_name,
                COUNT(*) as transaction_count,
                SUM(transactions.total) as total_sales,
                AVG(transactions.total) as average_amount
            ')
            ->orderByDesc('total_sales')
            ->paginate($perPage);
    }

    /**
     * Laporan penjualan per area servis/meja (SalesReport tab service_areas).
     */
    public static function getSalesByServiceAreaReport(Carbon $start, Carbon $end, int $perPage = 10): LengthAwarePaginator
    {
        return static::query()
            ->join('service_areas', 'transactions.service_area_id', '=', 'service_areas.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('service_areas.id', 'service_areas.name')
            ->selectRaw('
                service_areas.name as area_name,
                COUNT(transactions.id) as transaction_count,
                SUM(transactions.total) as total_sales,
                AVG(transactions.total) as average_amount
            ')
            ->orderByDesc('transaction_count')
            ->paginate($perPage);
    }

    /**
     * Query ringkasan penjualan per metode pembayaran (untuk Export).
     */
    public static function getSalesByPaymentMethodExportQuery(Carbon $start, Carbon $end)
    {
        return static::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('payment_method')
            ->selectRaw('
                payment_method,
                COUNT(*) as transaction_count,
                SUM(total) as total_amount,
                AVG(total) as average_amount
            ')
            ->orderByDesc('total_amount');
    }

    // -----------------------------------------------------------------
    // Status helpers
    // -----------------------------------------------------------------

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isCash(): bool
    {
        return $this->payment_method === 'cash';
    }

    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    public static function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');

        $lastInvoice = static::whereDate('created_at', today())
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
            $sequence = $lastSequence + 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $date, $sequence);
    }

    public function complete(float $paidAmount): bool
    {
        $change = $paidAmount - $this->total;

        return $this->update([
            'status'        => 'completed',
            'paid_amount'   => $paidAmount,
            'change_amount' => max(0, $change),
        ]);
    }

    public function cancel(int $cancelledBy, string $reason): bool
    {
        return DB::transaction(function () use ($cancelledBy, $reason) {
            $this->update([
                'status'           => 'cancelled',
                'cancelled_by'     => $cancelledBy,
                'cancelled_reason' => $reason,
                'cancelled_at'     => now(),
            ]);

            // Create refund expense if transaction was completed with cash
            if ($this->isCompleted() && $this->isCash() && $this->shift_id) {
                ShiftExpense::create([
                    'shift_id'    => $this->shift_id,
                    'order_id'    => $this->id,
                    'description' => "Refund: {$this->invoice_number}",
                    'amount'      => $this->total,
                    'category'    => 'refund',
                ]);
            }

            return true;
        });
    }

    /**
     * Get transaction with details loaded for printing.
     */
    public static function getForPrinting(int $id): ?self
    {
        return static::with(['details.modifiers', 'paymentSource', 'user', 'serviceArea'])->find($id);
    }

    /**
     * Get a completed transaction by invoice number.
     */
    public static function getCompletedByInvoice(string $invoiceNumber): ?self
    {
        return static::with(['details.modifiers'])
            ->where('invoice_number', $invoiceNumber)
            ->where('status', 'completed')
            ->first();
    }

    /**
     * Get today's completed transactions for a user (termasuk self-order).
     */
    public static function getTodayUserTransactions(int $userId): Collection
    {
        return static::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere(function ($sub) {
                  $sub->whereNull('user_id')
                      ->where('source', 'self_order');
              });
        })
            ->whereDate('created_at', today())
            ->where('status', 'completed')
            ->latest()
            ->get();
    }

    /**
     * Get recent transactions with details.
     */
    public static function getRecentTransactions(int $limit = 10): Collection
    {
        return static::with(['user', 'details'])
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * Get paginated transaction history for admin view with eager loading (Anti N+1).
     */
    public static function getPaginatedHistory(
        Carbon $start,
        Carbon $end,
        ?string $search = null,
        ?int $cashierId = null,
        ?string $orderType = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return static::with(['user', 'paymentSource', 'selfOrder'])
            ->filter($start, $end, $search, $cashierId, $orderType)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get transaction query for export.
     */
    public static function getExportQuery(
        Carbon $start,
        Carbon $end,
        ?string $search = null,
        ?int $cashierId = null,
        ?string $orderType = null
    ) {
        return static::with(['paymentSource', 'user', 'selfOrder'])
            ->filter($start, $end, $search, $cashierId, $orderType)
            ->latest();
    }


    /**
     * Get transaction detail with full details and related entities.
     */
    public static function getForDetail(int $id): self
    {
        return static::with(['user', 'details.product.category', 'details.modifiers', 'paymentSource', 'selfOrder'])->findOrFail($id);
    }

    /**
     * Get paginated transactions for admin transactions list.
     */
    public static function getPaginatedForAdminList(
        ?string $search = null,
        ?string $filterStatus = null,
        ?string $filterDate = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return static::with(['user', 'paymentSource'])
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%")->orWhere('customer_name', 'like', "%{$search}%"))
            ->when($filterStatus, fn($q) => $q->where('status', $filterStatus))
            ->when($filterDate, fn($q) => $q->whereDate('created_at', $filterDate))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get today's stats summary (completed & cancelled).
     */
    public static function getTodayStats(): array
    {
        $today = Carbon::today();
        
        $transactions = static::query()
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->get();

        $cancelled = static::query()
            ->whereDate('cancelled_at', $today)
            ->where('status', 'cancelled')
            ->count();

        return [
            'total_sales' => $transactions->sum('total'),
            'transaction_count' => $transactions->count(),
            'average_transaction' => $transactions->count() > 0 
                ? $transactions->sum('total') / $transactions->count() 
                : 0,
            'cancelled_count' => $cancelled,
        ];
    }

    /**
     * Get transactions for print table (summary print view).
     */
    public static function getForPrintTable(
        Carbon $start,
        Carbon $end,
        ?string $search = null,
        ?int $cashierId = null
    ): \Illuminate\Database\Eloquent\Collection {
        return static::with(['user', 'paymentSource'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest()
            ->get();
    }

    /**
     * Get transactions with full detail for print detail view.
     */
    public static function getForPrintDetail(
        Carbon $start,
        Carbon $end,
        ?string $search = null,
        ?int $cashierId = null
    ): \Illuminate\Database\Eloquent\Collection {
        return static::with(['user', 'paymentSource', 'details.product', 'details.modifiers'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest()
            ->get();
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp ' . number_format($this->total, 0, ',', '.');
    }

    public function getQueueDisplayAttribute(): string
    {
        return str_pad($this->queue_number, 3, '0', STR_PAD_LEFT);
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return match ($this->order_type) {
            'dine_in'   => 'Makan di Tempat',
            'take_away' => 'Bawa Pulang',
            default     => $this->order_type ?? '-',
        };
    }

    /**
     * Increment print count for a transaction by ID.
     */
    public static function incrementPrintCount(int $id): void
    {
        static::where('id', $id)->increment('print_count');
    }

    /**
     * Load relationships needed for single transaction print receipt.
     */
    public function loadForPrintSingle(): self
    {
        return $this->load(['user', 'paymentSource', 'details.product', 'details.modifiers', 'details.product.category']);
    }

    /**
     * Cari transaksi lewat receipt_token untuk halaman struk publik
     * (/receipt/{token}) — sekaligus eager-load semua relasi yang dipakai
     * halaman itu supaya tidak N+1.
     */
    public static function findByReceiptToken(string $token): ?self
    {
        return static::with(['user', 'paymentSource', 'serviceArea', 'pager', 'details.product', 'details.modifiers'])
            ->where('receipt_token', $token)
            ->first();
    }
}

