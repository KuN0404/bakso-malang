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

        $transactions = Transaction::getForPrintTable($start, $end, $search, $cashierId);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_revenue'      => $transactions->sum('total'),
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

        $transactions = Transaction::getForPrintDetail($start, $end, $search, $cashierId);

        $summary = [
            'total_transactions' => $transactions->count(),
            'total_revenue'      => $transactions->sum('total'),
        ];

        return view('print.transactions-detail', compact('transactions', 'start', 'end', 'summary', 'format'));
    }

    public function returnsReport(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');

        $returns = \App\Models\ProductReturn::getForPrintReport($start, $end, $search);

        // Use model helper for summary calculations
        $summary = \App\Models\ProductReturn::getReturnsSummary($start, $end);
        $todayTotal   = $summary['today_total'];
        $returnsCount = $summary['returns_count'];
        $returnsQty   = $summary['returns_qty'];

        $format = $request->query('format', 'A4');
        return view('print.returns-report', compact('returns', 'start', 'end', 'todayTotal', 'returnsCount', 'returnsQty', 'format'));
    }
    public function returnDetail(Request $request, $id)
    {
        $return = \App\Models\ProductReturn::getForDetail($id);
        $format = $request->query('format', '58mm');
        return view('print.return-detail', compact('return', 'format'));
    }

    public function transactionSingle(Request $request, Transaction $transaction)
    {
        $format = $request->query('format', '58mm');
        Transaction::incrementPrintCount($transaction->id);

        // Load relationships
        $transaction->loadForPrintSingle();
        
        return view('print.transaction-single', compact('transaction', 'format'));
    }

    public function shiftsTable(Request $request)
    {
        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $cashierId = $request->query('cashier');
        $format = $request->query('format', 'A4');

        $shifts = \App\Models\Shift::getForPrintTable($start, $end, $cashierId);

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
        $shift->loadForPrintDetail();
        
        return view('print.shift-detail', compact('shift', 'format'));
    }

    public function shiftCustom(Request $request, \App\Models\Shift $shift)
    {
        $type = $request->query('type', 'brief');
        $format = $request->query('format', '58mm');

        // Eager load for performance
        $shift->loadForPrintCustom();

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

    public function inventoryReport(Request $request)
    {
        $start = Carbon::parse($request->query('start', now()->format('Y-m-d')))->startOfDay();
        $end = Carbon::parse($request->query('end', now()->format('Y-m-d')))->endOfDay();
        $tab = $request->query('tab', 'valuation');
        $search = $request->query('search');
        $format = $request->query('format', 'A4');

        $totalIngredients = \App\Models\Ingredient::count();
        $criticalStockCount = \App\Models\Ingredient::lowStock()->count();
        $totalAssetValue = \App\Models\Ingredient::select(\Illuminate\Support\Facades\DB::raw('SUM(stock * cost_price) as total_asset'))->value('total_asset') ?? 0;

        $ingredients = \App\Models\Ingredient::when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))->orderBy('name')->get();

        $components = \App\Models\Component::when($search, fn($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))->orderBy('name')->get();

        $purchases = \App\Models\Purchase::with(['items.ingredient', 'items.product', 'user'])
            ->whereBetween('purchase_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%")->orWhere('supplier_name', 'like', "%{$search}%"))
            ->orderByDesc('purchase_date')
            ->get();

        $productions = \App\Models\Production::with(['inputs.ingredient', 'outputs.component', 'outputs.product', 'user'])
            ->whereBetween('production_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->when($search, fn($q) => $q->where('production_code', 'like', "%{$search}%"))
            ->orderByDesc('production_date')
            ->get();

        $stockLogs = \App\Models\IngredientStockLog::with(['ingredient', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('ingredient', fn($ing) => $ing->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->get();

        $componentStockLogs = \App\Models\ComponentStockLog::with(['component', 'user'])
            ->whereBetween('created_at', [$start, $end])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('component', fn($comp) => $comp->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"));
            })
            ->orderByDesc('created_at')
            ->get();

        return view('print.inventory-report', compact(
            'start', 'end', 'tab', 'search', 'format',
            'totalIngredients', 'criticalStockCount', 'totalAssetValue',
            'ingredients', 'components', 'purchases', 'productions', 'stockLogs', 'componentStockLogs'
        ));
    }
}
