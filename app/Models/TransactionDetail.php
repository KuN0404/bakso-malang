<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_name',
        'unit_price',
        'quantity',
        'modifier_total',
        'subtotal',
        'notes',
        'status', // pending, done
        'fulfilled_at',
    ];

    protected $casts = [
        'unit_price'   => 'decimal:2',
        'modifier_total' => 'decimal:2',
        'subtotal'     => 'decimal:2',
        'quantity'     => 'integer',
        'fulfilled_at' => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relationships
    // -----------------------------------------------------------------

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function modifiers(): BelongsToMany
    {
        return $this->belongsToMany(Modifier::class, 'transaction_detail_modifier')
            ->withPivot(['modifier_name', 'price_adjustment', 'quantity']);
    }

    // -----------------------------------------------------------------
    // Static Query Methods (dipanggil dari Livewire/Controller)
    // -----------------------------------------------------------------

    /**
     * Laporan penjualan per produk dengan paginasi (SalesReport tab products).
     */
    public static function getProductSalesReport(
        Carbon $start,
        Carbon $end,
        ?int $categoryId = null,
        int $perPage = 20
    ): LengthAwarePaginator {
        return static::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->selectRaw('
                products.id as product_id,
                products.name as product_name,
                categories.name as category_name,
                SUM(transaction_details.quantity) as total_qty,
                AVG(transaction_details.unit_price) as avg_price,
                SUM(transaction_details.subtotal) as total_revenue
            ')
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('total_revenue')
            ->paginate($perPage);
    }

    /**
     * Summary (total qty + revenue) untuk tab products di SalesReport.
     */
    public static function getProductSalesSummary(
        Carbon $start,
        Carbon $end,
        ?int $categoryId = null
    ): array {
        $base = static::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId));

        return [
            'total_qty'     => (clone $base)->sum('transaction_details.quantity'),
            'total_revenue' => (clone $base)->sum('transaction_details.subtotal'),
        ];
    }

    /**
     * Laporan penjualan per kategori (untuk Export).
     */
    public static function getCategorySalesReport(Carbon $start, Carbon $end): Collection
    {
        return static::query()
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('
                categories.name as category_name,
                SUM(transaction_details.quantity) as total_qty,
                COUNT(DISTINCT transactions.id) as transaction_count,
                SUM(transaction_details.subtotal) as total_sales
            ')
            ->orderByDesc('total_sales')
            ->get();
    }

    /**
     * Laporan penjualan per produk (untuk Export — menggunakan cursor).
     */
    public static function getProductSalesExportQuery(Carbon $start, Carbon $end, ?int $categoryId = null)
    {
        return static::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when($categoryId, fn($q) => $q->where('products.category_id', $categoryId))
            ->selectRaw('
                products.name as product_name,
                categories.name as category_name,
                SUM(transaction_details.quantity) as total_qty,
                AVG(transaction_details.unit_price) as avg_price,
                SUM(transaction_details.subtotal) as total_revenue
            ')
            ->groupBy('products.id', 'products.name', 'categories.name')
            ->orderByDesc('total_qty');
    }

    /**
     * Query detail transaksi penjualan ter-join (untuk Export CSV Detail).
     */
    public static function getTransactionsDetailExportQuery(Carbon $start, Carbon $end, ?string $search = null, ?int $cashierId = null)
    {
        return \Illuminate\Support\Facades\DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->leftJoin('payment_sources', 'transactions.payment_source_id', '=', 'payment_sources.id')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', 'completed')
            ->when($search, fn($q) => $q->where('transactions.invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('transactions.user_id', $cashierId))
            ->orderBy('transactions.created_at', 'desc')
            ->orderBy('transactions.id', 'desc')
            ->select([
                'transactions.id as trans_id',
                'transactions.invoice_number',
                'transactions.created_at',
                'transactions.customer_name',
                'transactions.subtotal as trans_subtotal',
                'transactions.tax_amount',
                'transactions.total as trans_total',
                'users.name as cashier_name',
                'payment_sources.name as payment_name',
                'products.name as product_name',
                'transaction_details.unit_price',
                'transaction_details.quantity',
                'transaction_details.subtotal as item_subtotal',
                \Illuminate\Support\Facades\DB::raw('(SELECT GROUP_CONCAT(modifier_name SEPARATOR ", ") FROM transaction_detail_modifier WHERE transaction_detail_id = transaction_details.id) as modifiers_list')
            ]);
    }

    /**
     * Get pending items (food/drink) for a shift's completed transactions.
     */
    public static function getPendingItemsForShift(int $shiftId): Collection
    {
        return static::whereHas('transaction', function ($q) use ($shiftId) {
            $q->where('shift_id', $shiftId)->where('status', 'completed');
        })
        ->where('status', 'pending')
        ->with('product.category')
        ->get();
    }

    /**
     * Get pending items grouped by transaction for Kitchen Display.
     * Supports array of shift IDs and KitchenTarget classification.
     */
    public static function getKitchenQueue(array $shiftIds, \App\Enums\KitchenTarget|string $target): Collection
    {
        $targetValue = $target instanceof \App\Enums\KitchenTarget ? $target->value : $target;

        return static::query()
            ->with(['transaction.user', 'transaction.serviceArea', 'product.category', 'modifiers'])
            ->whereHas('transaction', function ($q) use ($shiftIds) {
                $q->when(!empty($shiftIds), fn($sq) => $sq->whereIn('shift_id', $shiftIds))
                  ->whereIn('status', ['pending', 'completed']);
            })
            ->where('status', 'pending')
            ->whereHas('product.category', function ($q) use ($targetValue) {
                $q->where('target_kitchen', $targetValue);
            })
            ->orderBy('created_at', 'asc')
            ->get();
    }


    // -----------------------------------------------------------------
    // Business Logic
    // -----------------------------------------------------------------

    public function calculateSubtotal(): float
    {
        $basePrice = $this->unit_price * $this->quantity;
        $modifierTotal = $this->modifier_total * $this->quantity;

        return $basePrice + $modifierTotal;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function markAsDone(): bool
    {
        return $this->update([
            'status'       => 'done',
            'fulfilled_at' => now(),
        ]);
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getFormattedSubtotalAttribute(): string
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    public function getModifierNamesAttribute(): string
    {
        return $this->modifiers->map(function ($m) {
            $qty = ($m->pivot->quantity ?? 1) > 1 ? " ×{$m->pivot->quantity}" : '';
            return $m->pivot->modifier_name . $qty;
        })->implode(', ');
    }
}
