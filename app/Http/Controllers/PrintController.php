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

        return view('print.transactions-table', compact('transactions', 'start', 'end', 'summary'));
    }

    public function transactionsDetail(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');
        $cashierId = $request->query('cashier');

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

        return view('print.transactions-detail', compact('transactions', 'start', 'end', 'summary'));
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

        return view('print.returns-report', compact('returns', 'start', 'end', 'todayTotal', 'returnsCount', 'returnsQty'));
    }
}
