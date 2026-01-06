<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PrintController extends Controller
{
    public function transactionsTable(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');
        $cashierId = $request->query('cashier');
        $format = $request->query('format', 'A4');

        $transactions = Transaction::with(['user', 'paymentSource'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest()
            ->get();

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total'),
        ];

        return view('print.transactions-table', compact('transactions', 'start', 'end', 'summary', 'format'));
    }

    public function transactionsDetail(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');
        $cashierId = $request->query('cashier');
        $format = $request->query('format', 'A4');

        $transactions = Transaction::with(['user', 'paymentSource', 'details.product', 'details.modifiers'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest()
            ->get();
            
        $summary = [
            'total_transactions' => $transactions->count(),
            'total_revenue' => $transactions->sum('total'),
        ];

        return view('print.transactions-detail', compact('transactions', 'start', 'end', 'summary', 'format'));
    }

    public function returnsReport(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');

        $returns = \App\Models\ProductReturn::with(['items.product', 'transaction', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, function($q) use ($search) {
                 $q->where('return_number', 'like', "%{$search}%")
                   ->orWhereHas('transaction', fn($st) => $st->where('invoice_number', 'like', "%{$search}%"));
            })
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculate Totals based on DATE RANGE (consistent with dashboard cards)
        $todayTotal = \App\Models\ProductReturn::whereBetween('created_at', [$start, $end])->sum('total_refund');
        $returnsCount = \App\Models\ProductReturn::whereBetween('created_at', [$start, $end])->count();
        $returnsQty = \App\Models\ReturnItem::whereHas('return', function($q) use ($start, $end) {
            $q->whereBetween('created_at', [$start, $end]);
        })->sum('quantity');

        $format = $request->query('format', 'A4');
        return view('print.returns-report', compact('returns', 'start', 'end', 'todayTotal', 'returnsCount', 'returnsQty', 'format'));
    }
    public function returnDetail(Request $request, $id)
    {
        $return = \App\Models\ProductReturn::with(['items.product', 'transaction', 'user'])->findOrFail($id);
        $format = $request->query('format', '58mm');
        return view('print.return-detail', compact('return', 'format'));
    }

    public function transactionSingle(Request $request, Transaction $transaction)
    {
        $format = $request->query('format', '58mm');
        $transaction->increment('print_count');

        // Load relationships
        $transaction->load(['user', 'paymentSource', 'details.product', 'details.modifiers', 'details.product.category']);
        
        return view('print.transaction-single', compact('transaction', 'format'));
    }

    public function shiftsTable(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $cashierId = $request->query('cashier');
        $format = $request->query('format', 'A4');

        $shifts = \App\Models\Shift::with(['user', 'transactions', 'expenses'])
            ->whereBetween('started_at', [$start, $end])
            ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
            ->latest('started_at')
            ->get();

        $summary = [
            'total_shifts' => $shifts->count(),
            'total_sales' => $shifts->sum(fn($s) => $s->transactions->where('status', 'completed')->sum('total')),
            'total_expenses' => $shifts->sum(fn($s) => $s->expenses->sum('amount')),
            'total_difference' => $shifts->sum('cash_difference'),
        ];

        return view('print.shifts-table', compact('shifts', 'start', 'end', 'summary', 'format'));
    }

    public function shiftDetail(Request $request, \App\Models\Shift $shift)
    {
        $format = $request->query('format', '58mm');
        $shift->load(['user', 'transactions.paymentSource', 'expenses']);
        
        return view('print.shift-detail', compact('shift', 'format'));
    }

    public function shiftCustom(Request $request, \App\Models\Shift $shift)
    {
        $type = $request->query('type', 'brief');
        $format = $request->query('format', '58mm');

        // Eager load for performance
        $shift->load(['user', 'expenses', 'transactions' => function($q) {
             $q->where('status', 'completed')->orderBy('created_at');
        }]);

        if ($type === 'detail') {
            if (in_array($format, ['A4', 'A5'])) {
                return view('print.shift-full-a4', compact('shift', 'format'));
            }
            return view('print.shift-full', compact('shift', 'format'));
        }

        // Brief (Standard)
        return view('print.shift-detail', compact('shift', 'format'));
    }

    public function salesReport(Request $request)
    {
        $start = Carbon::parse($request->query('start', now()->format('Y-m-d')))->startOfDay();
        $end = Carbon::parse($request->query('end', now()->format('Y-m-d')))->endOfDay();
        $format = $request->query('format', 'A4');
        
        $reportService = new \App\Services\ReportService();
        
        // Fetch Data
        $summary = $reportService->getRangeSummaryReport($start, $end);
        $categories = $reportService->getSalesByCategoryReport($start, $end);
        $payments = $reportService->getPaymentMethodReport($start, $end);
        $topProducts = $reportService->getTopProductsReport($start, $end, 10); // Top 10
        
        return view('print.sales-report', compact(
            'start', 'end', 'format',
            'summary', 'categories', 'payments', 'topProducts'
        ));
    }
}
