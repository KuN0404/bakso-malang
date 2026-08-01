<?php

namespace App\Livewire\Admin;

use App\Models\ProductReturn;
use App\Models\Transaction;
use App\Models\ShiftExpense;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

#[Layout('layouts.admin')]
class Returns extends Component
{
    use WithPagination;

    // Filter Properties
    public string $search = '';
    public string $periodType = 'daily';
    public string $startDate = '';
    public string $endDate = '';
    public $selectedWeek;
    public $selectedWeekYear;
    public $selectedMonth;
    public $selectedYear;

    // Detail
    public bool $showDetailModal = false;
    public ?ProductReturn $selectedReturn = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'periodType' => ['except' => 'daily'],
        'startDate' => ['except' => ''],
        'endDate' => ['except' => ''],
        'selectedWeek' => ['except' => null],
        'selectedWeekYear' => ['except' => null],
        'selectedMonth' => ['except' => null],
        'selectedYear' => ['except' => null],
    ];

    public function mount(): void
    {
        $this->startDate = request('startDate', now()->format('Y-m-d'));
        $this->endDate = request('endDate', now()->format('Y-m-d'));
        $this->selectedWeek = request('selectedWeek', now()->weekOfYear);
        $this->selectedWeekYear = request('selectedWeekYear', now()->year);
        $this->selectedMonth = request('selectedMonth', now()->format('Y-m'));
        $this->selectedYear = request('selectedYear', now()->year);
        $this->periodType = request('periodType', 'daily');
        
        // Initial calculation if needed, or rely on URL params
        if (!request()->has('startDate')) {
            $this->updateDateRange();
        }
    }

    public function updatedPeriodType()
    {
        $this->updateDateRange();
    }

    public function updatedSelectedWeek() { $this->updateDateRange(); }
    public function updatedSelectedWeekYear() { $this->updateDateRange(); }
    public function updatedSelectedMonth() { $this->updateDateRange(); }
    public function updatedSelectedYear() { $this->updateDateRange(); }

    public function updateDateRange(): void
    {
        switch ($this->periodType) {
            case 'daily':
                if ($this->startDate !== now()->format('Y-m-d') && $this->endDate !== now()->format('Y-m-d')) {
                     // Keep existing dates if set to something custom
                } else {
                     $this->startDate = now()->format('Y-m-d');
                     $this->endDate = now()->format('Y-m-d');
                }
                break;
            case 'weekly':
                $date = now()->setISODate($this->selectedWeekYear, $this->selectedWeek);
                $this->startDate = $date->startOfWeek()->format('Y-m-d');
                $this->endDate = $date->endOfWeek()->format('Y-m-d');
                break;
            case 'monthly':
                $date = \Carbon\Carbon::createFromFormat('Y-m', $this->selectedMonth);
                $this->startDate = $date->startOfMonth()->format('Y-m-d');
                $this->endDate = $date->endOfMonth()->format('Y-m-d');
                break;
            case 'yearly':
                $date = \Carbon\Carbon::createFromDate($this->selectedYear, 1, 1);
                $this->startDate = $date->startOfYear()->format('Y-m-d');
                $this->endDate = $date->endOfYear()->format('Y-m-d');
                break;
        }
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
        $this->resetPage();
    }
    
    public function applyDateRange(): void
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->periodType = 'daily';
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->selectedMonth = now()->format('Y-m');
        $this->selectedYear = now()->format('Y');
        $this->selectedWeek = now()->weekOfYear;
        $this->selectedWeekYear = now()->year;
        $this->search = '';
        $this->resetPage();
        
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
    }

    // Reset pagination when search changes
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->selectedReturn = ProductReturn::getForDetail($id);
        $this->showDetailModal = true;
    }

    public function getExportUrl(): string
    {
        return route('export.product-returns', [
            'start' => $this->startDate,
            'end' => $this->endDate,
        ]);
    }

    public function render()
    {
        $start = \Carbon\Carbon::parse($this->startDate)->startOfDay();
        $end = \Carbon\Carbon::parse($this->endDate)->endOfDay();

        $returns = ProductReturn::getPaginatedReport($start, $end, $this->search, 15);

        $summary = ProductReturn::getReturnsSummary($start, $end);
        $todayTotal = $summary['today_total'];
        $returnsCount = $summary['returns_count'];
        $returnsQty = $summary['returns_qty'];

        return view('livewire.admin.returns', compact('returns', 'todayTotal', 'returnsCount', 'returnsQty'))
            ->title('Laporan Retur');
    }
}
