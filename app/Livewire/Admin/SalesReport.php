<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\ReportService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class SalesReport extends Component
{
    use WithPagination;

    // Filter Logic
    public string $periodType = 'daily'; // daily, weekly, monthly, yearly
    public string $startDate;
    public string $endDate;
    public string $selectedMonth;
    public string $selectedYear;
    public int $selectedWeek;
    public int $selectedWeekYear;
    
    // Tabs
    public string $activeTab = 'analysis'; // analysis | products | categories | payments

    // Product Filter
    public ?int $categoryId = null;

    protected $queryString = [
        'periodType' => ['except' => 'daily'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'categoryId' => ['except' => null],
        'activeTab' => ['except' => 'analysis'],
        'selectedWeek' => ['except' => ''],
        'selectedWeekYear' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
    ];

    public function mount(): void
    {
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->format('Y');
        
        $now = now();
        $this->selectedMonth = $now->format('Y-m');
        $this->selectedYear = $now->year;
        $this->selectedWeek = $now->weekOfYear;
        $this->selectedWeekYear = $now->year;
        
        $this->startDate = $now->format('Y-m-d');
        $this->endDate = $now->format('Y-m-d');
    }

    public function updatedPeriodType()
    {
        $this->updateDateRange();
    }

    public function updatedSelectedMonth()
    {
        $this->updateDateRange();
    }

    public function updatedSelectedYear()
    {
        $this->updateDateRange();
    }

    public function updatedSelectedWeek()
    {
        $this->updateDateRange();
    }

    public function updatedSelectedWeekYear()
    {
        $this->updateDateRange();
    }
    
    public function setPeriodType(string $type): void
    {
        $this->periodType = $type;
        $this->updateDateRange();
    }

    public function updateDateRange()
    {
        switch ($this->periodType) {
            case 'daily':
                // Reset to today when clicking "Per Hari"
                $this->startDate = now()->format('Y-m-d');
                $this->endDate = now()->format('Y-m-d');
                break;
            case 'weekly':
                $start = Carbon::now()->setISODate($this->selectedWeekYear, $this->selectedWeek)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $this->startDate = $start->format('Y-m-d');
                $this->endDate = $end->format('Y-m-d');
                break;
            case 'monthly':
                $date = Carbon::createFromFormat('Y-m', $this->selectedMonth);
                $this->startDate = $date->startOfMonth()->format('Y-m-d');
                $this->endDate = $date->endOfMonth()->format('Y-m-d');
                break;
            case 'yearly':
                $date = Carbon::createFromFormat('Y', $this->selectedYear);
                $this->startDate = $date->startOfYear()->format('Y-m-d');
                $this->endDate = $date->endOfYear()->format('Y-m-d');
                break;
        }
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
        $this->resetPage();
    }
    
    // Get available weeks for current/selected year
    public function getWeeksProperty(): array
    {
        $weeks = [];
        $year = $this->selectedWeekYear ? $this->selectedWeekYear : now()->year;
        $totalWeeks = Carbon::createFromDate($year, 12, 28)->weekOfYear;
        
        for ($i = 1; $i <= $totalWeeks; $i++) {
            $startOfWeek = Carbon::now()->setISODate($year, $i)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();
            $weeks[$i] = "Minggu $i ({$startOfWeek->format('d M')} - {$endOfWeek->format('d M')})";
        }
        
        // Reverse array to show latest weeks first
        return array_reverse($weeks, true);
    }

    // Get available years (last 5 years)
    public function getYearsProperty(): array
    {
        $currentYear = now()->year;
        $years = [];
        for ($i = $currentYear; $i >= $currentYear - 4; $i--) {
            $years[$i] = $i;
        }
        return $years;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function applyDateRange(): void
    {
        // Manual date range picked by user via Flatpickr
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->periodType = 'daily';
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->format('Y');
        $this->categoryId = null;
        $this->resetPage();
        
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
    }

    public function getExportUrl(): string
    {
        // Route to different exports based on Active Tab
        if ($this->activeTab === 'products') {
            return route('export.product-sales', [
                'start' => $this->startDate,
                'end' => $this->endDate,
                'category' => $this->categoryId,
            ]);
        }
        
        if ($this->activeTab === 'categories') {
            return route('export.sales-by-category', [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ]);
        }

        if ($this->activeTab === 'payments') {
            return route('export.sales-by-payment-method', [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ]);
        }
        
        return '#'; 
    }

    public function render()
    {
        $reportService = new ReportService();
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        // Data for Analysis Tab
        $categoryReport = [];
        $paymentReport = [];
        $serviceAreaReport = [];
        $topProducts = [];
        $peakHours = [];
        $dailySummary = $reportService->getRangeSummaryReport($start, $end);

        if ($this->activeTab === 'analysis') {
            $categoryReport = $reportService->getSalesByCategoryReport($start, $end)->take(10);
            $paymentReport = $reportService->getPaymentMethodReport($start, $end)->take(10);
            $topProducts = $reportService->getTopProductsReport($start, $end, 10);
            $peakHours = $reportService->getPeakHoursRangeReport($start, $end)->take(10);
        }

        if ($this->activeTab === 'categories') {
            $categoryReport = Transaction::getSalesByCategoryReport($start, $end);
        }

        if ($this->activeTab === 'payments') {
            $paymentReport = Transaction::getSalesByPaymentReport($start, $end);
        }

        if ($this->activeTab === 'service_areas') {
            $serviceAreaReport = Transaction::getSalesByServiceAreaReport($start, $end);
        }

        // Data for Products Tab
        $productSales = null;
        $categories = [];
        $productSummary = [];

        if ($this->activeTab === 'products') {
            $categories = Category::getAllSortedByName();
            $productSales = TransactionDetail::getProductSalesReport($start, $end, $this->categoryId);
            
            $summaryData = TransactionDetail::getProductSalesSummary($start, $end, $this->categoryId);
            $productSummary = [
                'total_products' => $productSales->total(),
                'total_qty' => $summaryData['total_qty'],
                'total_revenue' => $summaryData['total_revenue'],
            ];
        }

        return view('livewire.admin.sales-report', compact(
            'categoryReport', 
            'paymentReport', 
            'serviceAreaReport',
            'topProducts', 
            'dailySummary', 
            'peakHours',
            'productSales',
            'categories',
            'productSummary'
        ))->title('Analisa & Performa');
    }
}
