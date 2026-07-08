<?php

namespace App\Services;

use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Laporan arus kas per shift dengan validasi selisih
     */
    public function getShiftCashFlowReport(Shift $shift): array
    {
        // Use eager loading to avoid N+1
        $shift->load(['completedTransactions', 'expenses', 'user']);
        
        $transactions = $shift->completedTransactions;
        
        $totalCash = $transactions
            ->where('payment_method', 'cash')
            ->sum('total');

        $totalNonCash = $transactions
            ->where('payment_method', '!=', 'cash')
            ->sum('total');

        $expenses = $shift->expenses->sum('amount');
        $refunds = $shift->expenses->where('category', 'refund')->sum('amount');
        $operational = $shift->expenses->where('category', '!=', 'refund')->sum('amount');

        // Cash: Opening + Cash Sales - ALL Expenses (cash-side only)
        $expectedCash = $shift->opening_cash + $totalCash - $expenses;

        return [
            'shift'                  => $shift,
            'opening_cash'           => $shift->opening_cash,
            'total_cash_sales'       => $totalCash,
            'total_non_cash_sales'   => $totalNonCash,
            'total_sales'            => $totalCash + $totalNonCash,
            'transaction_count'      => $transactions->count(),
            'refunds'                => $refunds,
            'operational_expenses'   => $operational,
            'total_expenses'         => $expenses,
            // Cash reconciliation
            'expected_cash'          => $expectedCash,
            'actual_cash'            => $shift->actual_cash,
            'difference'             => $shift->cash_difference,
            // Non-cash reconciliation
            'expected_non_cash'      => $shift->expected_non_cash ?? $totalNonCash,
            'actual_non_cash'        => $shift->actual_non_cash,
            'non_cash_difference'    => $shift->non_cash_difference,
            'status'                 => $shift->status,
        ];
    }

    /**
     * Laporan penjualan per kategori
     */
    public function getSalesByCategoryReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return TransactionDetail::query()
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('categories.id', 'categories.name')
            ->selectRaw('
                categories.id as category_id,
                categories.name as category_name, 
                SUM(transaction_details.subtotal) as total_sales, 
                SUM(transaction_details.quantity) as total_quantity,
                COUNT(DISTINCT transactions.id) as transaction_count
            ')
            ->orderByDesc('total_sales')
            ->get();
    }

    /**
     * Laporan metode pembayaran
     */
    public function getPaymentMethodReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->leftJoin('payment_sources', 'transactions.payment_source_id', '=', 'payment_sources.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('transactions.payment_method', 'payment_sources.name')
            ->selectRaw('
                CASE 
                    WHEN transactions.payment_method = "cash" THEN "Tunai"
                    ELSE COALESCE(payment_sources.name, transactions.payment_method)
                END as payment_name,
                COUNT(*) as transaction_count,
                SUM(transactions.total) as total_amount,
                AVG(transactions.total) as average_amount
            ')
            ->orderByDesc('total_amount')
            ->get();
    }

    /**
     * Laporan jam sibuk (peak hours)
     */
    public function getPeakHoursReport(Carbon $date): Collection
    {
        return Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->selectRaw('
                HOUR(created_at) as hour, 
                COUNT(*) as transaction_count, 
                SUM(total) as total_sales,
                AVG(total) as average_transaction
            ')
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->map(function ($item) {
                $item->hour_label = sprintf('%02d:00 - %02d:59', $item->hour, $item->hour);
                return $item;
            });
    }

    /**
     * Laporan peak hours dalam rentang tanggal (untuk analisis pola)
     */
    public function getPeakHoursRangeReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                HOUR(created_at) as hour, 
                COUNT(*) as transaction_count, 
                SUM(total) as total_sales,
                AVG(total) as average_transaction
            ')
            ->groupBy('hour')
            ->orderByDesc('transaction_count')
            ->get()
            ->map(function ($item) {
                $item->hour_label = sprintf('%02d:00 - %02d:59', $item->hour, $item->hour);
                return $item;
            });
    }

    /**
     * Laporan pembatalan pesanan yang mencurigakan
     * Flag users with more than 3 cancellations in given period
     */
    public function getSuspiciousCancellationsReport(Carbon $startDate, Carbon $endDate, int $threshold = 3): Collection
    {
        return Transaction::query()
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->with(['cancelledBy:id,name,email', 'user:id,name'])
            ->selectRaw('
                cancelled_by, 
                COUNT(*) as cancel_count, 
                SUM(total) as total_cancelled
            ')
            ->groupBy('cancelled_by')
            ->having('cancel_count', '>=', $threshold)
            ->orderByDesc('cancel_count')
            ->get();
    }

    /**
     * Laporan cancellation detail per user
     */
    public function getCancellationDetailReport(Carbon $startDate, Carbon $endDate): Collection
    {
        return Transaction::query()
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->with(['cancelledBy:id,name', 'user:id,name', 'details'])
            ->orderByDesc('cancelled_at')
            ->get();
    }

    /**
     * Laporan ringkasan harian
     */
    public function getDailySummaryReport(string|Carbon $date): array
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        $transactions = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', $date)
            ->get();

        $cancelled = Transaction::query()
            ->where('status', 'cancelled')
            ->whereDate('cancelled_at', $date)
            ->get();

        return [
            'date' => $date->format('Y-m-d'),
            'total_sales' => $transactions->sum('total'),
            'completed_count' => $transactions->count(),
            'average_transaction' => $transactions->count() > 0 
                ? $transactions->sum('total') / $transactions->count() 
                : 0,
            'cancelled_count' => $cancelled->count(),
            'cancelled_amount' => $cancelled->sum('total'),
            'payment_breakdown' => $transactions->groupBy('payment_method')->map->sum('total'),
        ];
    }

    /**
     * Laporan produk terlaris
     */
    public function getTopProductsReport(Carbon $startDate, Carbon $endDate, int $limit = 10): Collection
    {
        return TransactionDetail::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->groupBy('transaction_details.product_id', 'transaction_details.product_name')
            ->selectRaw('
                transaction_details.product_id,
                transaction_details.product_name,
                SUM(transaction_details.quantity) as total_quantity,
                SUM(transaction_details.subtotal) as total_sales
            ')
            ->orderByDesc('total_quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Laporan ringkasan berdasarkan rentang tanggal
     */
    public function getRangeSummaryReport(Carbon $startDate, Carbon $endDate): array
    {
        $rangeTransactions = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as count, SUM(total) as total')
            ->first();

        $cancelledCount = Transaction::query()
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startDate, $endDate])
            ->count();

        return [
            'total_sales' => $rangeTransactions->total ?? 0,
            'completed_count' => $rangeTransactions->count ?? 0,
            'average_transaction' => ($rangeTransactions->count > 0) ? ($rangeTransactions->total / $rangeTransactions->count) : 0,
            'cancelled_count' => $cancelledCount,
        ];
    }
}
