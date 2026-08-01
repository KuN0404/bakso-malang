<?php

namespace App\Livewire\Admin;

use App\Models\Shift;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Laporan Shift')]
class ShiftReport extends Component
{
    use WithPagination;

    public string $filterDate = ''; // Deprecated, will use startDate/endDate
    public ?int $filterUserId = null;
    public ?Shift $selectedShift = null;
    public bool $showDetailModal = false;

    // Period type: daily, weekly, monthly, yearly
    #[Url(except: 'daily')]
    public string $periodType = 'daily';

    // For daily: specific date or range
    public string $startDate;
    public string $endDate;

    // For weekly
    #[Url(except: '')]
    public int $selectedWeek;
    #[Url(except: '')]
    public int $selectedWeekYear;

    // For monthly
    #[Url(except: '')]
    public int $selectedMonth;
    #[Url(except: '')]
    public int $selectedMonthYear;

    // For yearly
    #[Url(except: '')]
    public int $selectedYear;

    public function mount(): void
    {
        $now = now();
        $this->filterDate = $now->format('Y-m-d');
        
        $this->startDate = $now->format('Y-m-d');
        $this->endDate = $now->format('Y-m-d');
        $this->selectedWeek = (int) $now->weekOfYear;
        $this->selectedWeekYear = $now->year;
        $this->selectedMonth = $now->month;
        $this->selectedMonthYear = $now->year;
        $this->selectedYear = $now->year;

        $this->applyPeriod();
    }

    public function updatedPeriodType(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    public function updatedSelectedWeek(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    public function updatedSelectedWeekYear(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    public function updatedSelectedMonth(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    public function updatedSelectedMonthYear(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    public function updatedSelectedYear(): void
    {
        $this->applyPeriod();
        $this->resetPage();
    }

    private function applyPeriod(): void
    {
        switch ($this->periodType) {
            case 'weekly':
                $start = Carbon::now()->setISODate($this->selectedWeekYear, $this->selectedWeek)->startOfWeek();
                $end = $start->copy()->endOfWeek();
                $this->startDate = $start->format('Y-m-d');
                $this->endDate = $end->format('Y-m-d');
                break;

            case 'monthly':
                $start = Carbon::createFromDate($this->selectedMonthYear, $this->selectedMonth, 1)->startOfMonth();
                $end = $start->copy()->endOfMonth();
                $this->startDate = $start->format('Y-m-d');
                $this->endDate = $end->format('Y-m-d');
                break;

            case 'yearly':
                $start = Carbon::createFromDate($this->selectedYear, 1, 1)->startOfYear();
                $end = $start->copy()->endOfYear();
                $this->startDate = $start->format('Y-m-d');
                $this->endDate = $end->format('Y-m-d');
                break;

            case 'daily':
            default:
                $now = now();
                $this->startDate = $now->format('Y-m-d');
                $this->endDate = $now->format('Y-m-d');
                break;
        }
    }

    public function applyDateRange(): void
    {
        $this->periodType = 'daily';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $now = now();
        $this->filterUserId = null;
        $this->periodType = 'daily';
        
        $this->startDate = $now->format('Y-m-d');
        $this->endDate = $now->format('Y-m-d');
        
        $this->selectedWeek = (int) $now->weekOfYear;
        $this->selectedWeekYear = $now->year;
        $this->selectedMonth = $now->month;
        $this->selectedMonthYear = $now->year;
        $this->selectedYear = $now->year;
        
        $this->resetPage();
        
        $this->dispatch('reset-date-picker', start: $this->startDate, end: $this->endDate);
    }

    // Get available weeks for current year
    public function getWeeksProperty(): array
    {
        $weeks = [];
        $year = $this->selectedWeekYear;
        $totalWeeks = Carbon::createFromDate($year, 12, 28)->weekOfYear;
        
        for ($i = 1; $i <= $totalWeeks; $i++) {
            $startOfWeek = Carbon::now()->setISODate($year, $i)->startOfWeek();
            $endOfWeek = $startOfWeek->copy()->endOfWeek();
            $weeks[$i] = "Minggu $i ({$startOfWeek->format('d M')} - {$endOfWeek->format('d M')})";
        }
        
        return $weeks;
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

    public function getExportUrl(): string
    {
        return route('export.shifts', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'cashier' => $this->filterUserId,
        ]);
    }

    public function getExportDetailUrl(): string
    {
        return route('export.transactions.detail', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'cashier' => $this->filterUserId,
        ]);
    }

    public function getPrintTableUrl(): string
    {
        return route('print.shifts.table', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'cashier' => $this->filterUserId,
        ]);
    }

    public function viewDetail(int $id): void
    {
        $this->selectedShift = Shift::getForDetail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $shifts = Shift::getShiftReport($start, $end, $this->filterUserId, 15);
        $summary = Shift::getShiftSummary($start, $end, $this->filterUserId);
        
        $totalSales = $summary['total_sales'];
        $totalExpenses = $summary['total_expenses'];
        $totalDifference = $summary['total_difference'];

        $users = User::getAllSortedByName();

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('livewire.admin.shift-report', compact('shifts', 'users', 'totalSales', 'totalExpenses', 'totalDifference', 'months'));
    }
}
