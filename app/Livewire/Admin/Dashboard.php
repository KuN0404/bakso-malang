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
        return Transaction::getTodayStats();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'products' => Product::count(),
            'categories' => Category::count(),
            'active_shifts' => Shift::open()->count(),
        ];
    }

    #[Computed]
    public function recentTransactions()
    {
        return Transaction::getRecentTransactions(10);
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
