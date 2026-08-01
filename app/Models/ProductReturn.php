<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductReturn extends Model
{
    protected $table = 'returns';
    protected $guarded = ['id'];

    public static function generateReturnNumber()
    {
        $prefix = 'RTN-' . date('Ymd') . '-';
        $last = self::where('return_number', 'like', $prefix . '%')->orderBy('id', 'desc')->first();
        if ($last) {
            $num = (int) Str::afterLast($last->return_number, '-') + 1;
        } else {
            $num = 1;
        }
        return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function items()
    {
        return $this->hasMany(ReturnItem::class, 'return_id');
    }

    /**
     * Query data retur untuk Export.
     */
    public static function getExportQuery(\Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return static::with(['transaction', 'user', 'items.product'])
            ->whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Get today's return records for a specific user.
     */
    public static function getTodayUserReturns(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['user', 'transaction'])
            ->where('user_id', $userId)
            ->whereDate('created_at', today())
            ->latest()
            ->get();
    }

    /**
     * Get paginated return report for the admin.
     */
    public static function getPaginatedReport(
        \Carbon\Carbon $start,
        \Carbon\Carbon $end,
        ?string $search = null,
        int $perPage = 15
    ): \Illuminate\Contracts\Pagination\LengthAwarePaginator {
        return static::with(['transaction', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, fn($q) => $q->where('return_number', 'like', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get return totals summary (refund amount, count, return item qty).
     */
    public static function getReturnsSummary(\Carbon\Carbon $start, \Carbon\Carbon $end): array
    {
        $refundTotal = static::whereBetween('created_at', [$start, $end])->sum('total_refund');
        $returnsCount = static::whereBetween('created_at', [$start, $end])->count();
        $returnsQty = ReturnItem::whereHas('return', function ($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end]);
        })->sum('quantity');

        return [
            'today_total'   => (float) $refundTotal,
            'returns_count' => (int) $returnsCount,
            'returns_qty'   => (int) $returnsQty,
        ];
    }

    /**
     * Get return details with relationships.
     */
    public static function getForDetail(int $id): self
    {
        return static::with(['transaction', 'items.product', 'user', 'shift'])->findOrFail($id);
    }

    /**
     * Get returns list with relations for print view.
     */
    public static function getForPrintReport(\Carbon\Carbon $start, \Carbon\Carbon $end, ?string $search = null): \Illuminate\Database\Eloquent\Collection
    {
        return static::with(['items.product', 'transaction', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, function ($q) use ($search) {
                 $q->where('return_number', 'like', "%{$search}%")
                   ->orWhereHas('transaction', fn($st) => $st->where('invoice_number', 'like', "%{$search}%"));
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Load relationships for print/export return view.
     */
    public function loadForPrint(): self
    {
        return $this->load(['items.product', 'transaction', 'user', 'shift']);
    }
}

