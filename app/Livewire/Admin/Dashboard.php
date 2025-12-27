<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    #[Computed]
    public function todayStats(): array
    {
        $today = Carbon::today();
        
        $transactions = Transaction::query()
            ->whereDate('created_at', $today)
            ->where('status', 'completed')
            ->get();

        $cancelled = Transaction::query()
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

    #[Computed]
    public function counts(): array
    {
        return [
            'products' => Product::count(),
            'categories' => Category::count(),
            'active_shifts' => Shift::where('status', 'open')->count(),
        ];
    }

    #[Computed]
    public function recentTransactions()
    {
        return Transaction::with(['user', 'details'])
            ->latest()
            ->take(10)
            ->get();
    }

    #[Computed]
    public function topProducts()
    {
        $reportService = new ReportService();
        return $reportService->getTopProductsReport(
            Carbon::today()->startOfMonth(),
            Carbon::today(),
            5
        );
    }

    public function render()
    {
        return view('livewire.admin.dashboard')
            ->title('Dashboard');
    }
}
