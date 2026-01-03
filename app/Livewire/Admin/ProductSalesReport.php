<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ProductSalesReport extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $period = 'this_month';

    #[Url(except: '')]
    public ?int $categoryId = null;

    public string $startDate;
    public string $endDate;

    public function mount(): void
    {
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->applyPeriod();

        // URL validation
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }

        // Validate categoryId
        if ($this->categoryId && !Category::where('id', $this->categoryId)->exists()) {
            $this->categoryId = null;
        }
    }

    public function updatingCategoryId(): void
    {
        $this->resetPage();
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
        $this->applyPeriod();
        $this->resetPage();
    }

    private function applyPeriod(): void
    {
        $this->startDate = match($this->period) {
            'today' => now()->format('Y-m-d'),
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'this_week' => now()->startOfWeek()->format('Y-m-d'),
            'this_month' => now()->startOfMonth()->format('Y-m-d'),
            'last_month' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            default => $this->startDate,
        };
        $this->endDate = match($this->period) {
            'yesterday' => now()->subDay()->format('Y-m-d'),
            'last_month' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
            default => now()->format('Y-m-d'),
        };
    }

    public function applyDateRange(): void
    {
        $this->period = 'custom';
        $this->resetPage();
    }

    public function getExportUrl(): string
    {
        return route('export.product-sales', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'category' => $this->categoryId,
        ]);
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $productSales = TransactionDetail::query()
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('transactions.status', 'completed')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->when($this->categoryId, fn($q) => $q->where('products.category_id', $this->categoryId))
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
            ->paginate(20);

        // Smart pagination
        if ($productSales->lastPage() < $this->getPage() && $productSales->lastPage() > 0) {
            $this->setPage($productSales->lastPage());
        }

        // Summary
        $summary = [
            'total_products' => $productSales->total(),
            'total_qty' => TransactionDetail::query()
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->where('transactions.status', 'completed')
                ->whereBetween('transactions.created_at', [$start, $end])
                ->sum('transaction_details.quantity'),
            'total_revenue' => TransactionDetail::query()
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->where('transactions.status', 'completed')
                ->whereBetween('transactions.created_at', [$start, $end])
                ->sum('transaction_details.subtotal'),
        ];

        $categories = Category::orderBy('name')->get();

        return view('livewire.admin.product-sales-report', compact('productSales', 'summary', 'categories'))
            ->title('Laporan Penjualan per Produk');
    }
}
