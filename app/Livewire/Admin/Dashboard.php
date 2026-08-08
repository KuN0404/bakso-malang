<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Component as ComponentModel;
use App\Models\Ingredient;
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
    public function mount(): void
    {
        $alertCount = session('low_stock_alert_count');

        if ($alertCount) {
            $this->dispatch(
                'notify',
                type: 'warning',
                message: "{$alertCount} item stok bahan baku/komponen menipis atau habis — cek tab \"Stok Menipis\" di Laporan Inventori."
            );
        }
    }

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
    public function lowStockSummary(): array
    {
        return [
            'ingredients_low' => Ingredient::lowStock()->where('stock', '>', 0)->count(),
            'ingredients_out' => Ingredient::outOfStock()->count(),
            'components_low'  => ComponentModel::lowStock()->where('stock', '>', 0)->count(),
            'components_out'  => ComponentModel::outOfStock()->count(),
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
