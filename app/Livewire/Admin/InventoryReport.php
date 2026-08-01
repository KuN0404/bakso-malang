<?php

namespace App\Livewire\Admin;

use App\Models\Component as ComponentModel;
use App\Models\ComponentStockLog;
use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use App\Models\Production;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class InventoryReport extends Component
{
    use WithPagination;

    // Period Type Filters
    #[Url(except: 'daily')]
    public string $periodType = 'daily'; // daily, weekly, monthly, yearly

    #[Url(except: '')]
    public string $startDate = '';

    #[Url(except: '')]
    public string $endDate = '';

    #[Url(except: '')]
    public string $selectedMonth = '';

    #[Url(except: '')]
    public string $selectedMonthYear = '';

    #[Url(except: '')]
    public string $selectedYear = '';

    public int $selectedWeek = 1;
    public int $selectedWeekYear = 2026;

    // Tabs & Search
    #[Url(except: 'valuation')]
    public string $activeTab = 'valuation'; // valuation, components, purchases, productions, stock_logs, component_stock_logs

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;
    public string $printFormat = 'A4';

    public function mount(): void
    {
        $now = now();
        $this->selectedMonth = $now->format('m');
        $this->selectedMonthYear = (string) $now->year;
        $this->selectedYear = (string) $now->year;
        $this->selectedWeek = $now->weekOfYear;
        $this->selectedWeekYear = $now->year;

        if (empty($this->startDate)) {
            $this->startDate = $now->startOfMonth()->format('Y-m-d');
        }
        if (empty($this->endDate)) {
            $this->endDate = now()->format('Y-m-d');
        }
    }

    public function updatedPeriodType(): void
    {
        $this->updateDateRange();
    }

    public function updatedSelectedMonth(): void
    {
        $this->updateDateRange();
    }

    public function updatedSelectedMonthYear(): void
    {
        $this->updateDateRange();
    }

    public function updatedSelectedYear(): void
    {
        $this->updateDateRange();
    }

    public function updatedSelectedWeek(): void
    {
        $this->updateDateRange();
    }

    public function updatedSelectedWeekYear(): void
    {
        $this->updateDateRange();
    }

    public function updateDateRange(): void
    {
        if ($this->periodType === 'daily') {
            // Keep custom date range
        } elseif ($this->periodType === 'weekly') {
            $date = Carbon::now()->setISODate($this->selectedWeekYear, $this->selectedWeek);
            $this->startDate = $date->startOfWeek()->format('Y-m-d');
            $this->endDate = $date->endOfWeek()->format('Y-m-d');
        } elseif ($this->periodType === 'monthly') {
            $year = (int) ($this->selectedMonthYear ?: now()->year);
            $month = (int) ($this->selectedMonth ?: now()->month);
            $date = Carbon::createFromDate($year, $month, 1);
            $this->startDate = $date->startOfMonth()->format('Y-m-d');
            $this->endDate = $date->endOfMonth()->format('Y-m-d');
        } elseif ($this->periodType === 'yearly') {
            $year = (int) ($this->selectedYear ?: now()->year);
            $date = Carbon::createFromDate($year, 1, 1);
            $this->startDate = $date->startOfYear()->format('Y-m-d');
            $this->endDate = $date->endOfYear()->format('Y-m-d');
        }

        $this->resetPage();
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
    }

    public function applyDateRange(): void
    {
        $this->resetPage();
    }

    public function updatedActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function filterToday(): void
    {
        $this->periodType = 'daily';
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->resetPage();
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
    }

    public function filterThisMonth(): void
    {
        $this->periodType = 'monthly';
        $this->selectedMonth = now()->format('m');
        $this->selectedMonthYear = (string) now()->year;
        $this->updateDateRange();
    }

    public function getWeeksProperty(): array
    {
        $weeks = [];
        $year = $this->selectedWeekYear ?: now()->year;
        $date = Carbon::createFromDate($year, 1, 1);
        $totalWeeks = $date->weeksInYear();

        for ($i = 1; $i <= $totalWeeks; $i++) {
            $wDate = Carbon::now()->setISODate($year, $i);
            $start = $wDate->startOfWeek()->format('d M');
            $end = $wDate->endOfWeek()->format('d M Y');
            $weeks[$i] = "Minggu ke-{$i} ({$start} - {$end})";
        }

        return $weeks;
    }

    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
            $years[$y] = (string) $y;
        }
        return $years;
    }

    public function getMonthsProperty(): array
    {
        return [
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret',
            '04' => 'April', '05' => 'Mei', '06' => 'Juni',
            '07' => 'Juli', '08' => 'Agustus', '09' => 'September',
            '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
        ];
    }

    public function getPrintUrl(): string
    {
        return route('print.inventory-report', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'tab' => $this->activeTab,
            'search' => $this->search,
            'format' => $this->printFormat,
        ]);
    }

    public function render()
    {
        // Summary Stats
        $totalIngredients = Ingredient::count();
        $criticalStockCount = Ingredient::whereColumn('stock', '<=', 'minimum_stock')->count();
        $totalAssetValue = Ingredient::select(DB::raw('SUM(stock * cost_price) as total_asset'))->value('total_asset') ?? 0;

        $purchaseStats = Purchase::query()
            ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select(DB::raw('COUNT(*) as total_count, SUM(total_amount) as total_sum'))
            ->first();

        $totalPurchaseCount = $purchaseStats->total_count ?? 0;
        $totalPurchaseSum = $purchaseStats->total_sum ?? 0;

        $productionStats = Production::query()
            ->whereBetween('production_date', [$this->startDate, $this->endDate])
            ->where('status', 'completed')
            ->select(DB::raw('COUNT(*) as total_count, SUM(total_cost) as total_cost'))
            ->first();

        $totalProductionCount = $productionStats->total_count ?? 0;
        $totalProductionCost = $productionStats->total_cost ?? 0;

        // Data queries based on active tab
        $valuationData           = null;
        $componentsData          = null;
        $purchasesData           = null;
        $productionsData         = null;
        $stockLogsData           = null;
        $componentStockLogsData  = null;

        if ($this->activeTab === 'valuation') {
            $valuationData = Ingredient::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate($this->perPage);
        } elseif ($this->activeTab === 'components') {
            $componentsData = ComponentModel::query()
                ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"))
                ->orderBy('name')
                ->paginate($this->perPage);
        } elseif ($this->activeTab === 'purchases') {
            $purchasesData = Purchase::query()
                ->with(['items.ingredient', 'items.product', 'user'])
                ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
                ->when($this->search, fn($q) => $q->where('invoice_number', 'like', "%{$this->search}%")->orWhere('supplier_name', 'like', "%{$this->search}%"))
                ->orderByDesc('purchase_date')
                ->orderByDesc('id')
                ->paginate($this->perPage);
        } elseif ($this->activeTab === 'productions') {
            $productionsData = Production::query()
                ->with(['inputs.ingredient', 'outputs.component', 'outputs.product', 'user'])
                ->whereBetween('production_date', [$this->startDate, $this->endDate])
                ->when($this->search, fn($q) => $q->where('production_code', 'like', "%{$this->search}%"))
                ->orderByDesc('production_date')
                ->orderByDesc('id')
                ->paginate($this->perPage);
        } elseif ($this->activeTab === 'stock_logs') {
            $stockLogsData = IngredientStockLog::query()
                ->with(['ingredient', 'user'])
                ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
                ->when($this->search, function ($q) {
                    $q->whereHas('ingredient', fn($ing) => $ing->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"));
                })
                ->orderByDesc('created_at')
                ->paginate($this->perPage);
        } elseif ($this->activeTab === 'component_stock_logs') {
            $componentStockLogsData = ComponentStockLog::query()
                ->with(['component', 'user'])
                ->whereBetween('created_at', [Carbon::parse($this->startDate)->startOfDay(), Carbon::parse($this->endDate)->endOfDay()])
                ->when($this->search, function ($q) {
                    $q->whereHas('component', fn($comp) => $comp->where('name', 'like', "%{$this->search}%")->orWhere('code', 'like', "%{$this->search}%"));
                })
                ->orderByDesc('created_at')
                ->paginate($this->perPage);
        }

        return view('livewire.admin.inventory-report', [
            'totalIngredients'       => $totalIngredients,
            'criticalStockCount'     => $criticalStockCount,
            'totalAssetValue'        => $totalAssetValue,
            'totalPurchaseCount'     => $totalPurchaseCount,
            'totalPurchaseSum'       => $totalPurchaseSum,
            'totalProductionCount'   => $totalProductionCount,
            'totalProductionCost'    => $totalProductionCost,
            'valuationData'          => $valuationData,
            'componentsData'         => $componentsData,
            'purchasesData'          => $purchasesData,
            'productionsData'        => $productionsData,
            'stockLogsData'          => $stockLogsData,
            'componentStockLogsData' => $componentStockLogsData,
            'months'                 => $this->months,
        ])->title('Laporan Inventori & Produksi');
    }
}
