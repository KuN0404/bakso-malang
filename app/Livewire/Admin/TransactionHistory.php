<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class TransactionHistory extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?Transaction $selectedTransaction = null;
    
    // Receipt modal for printing
    public bool $showReceiptModal = false;
    public ?Transaction $lastTransaction = null;

    #[Url(except: '')]
    public string $search = '';



    #[Url(except: '')]
    public ?int $filterCashier = null;

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
        $this->startDate = $now->format('Y-m-d');
        $this->endDate = $now->format('Y-m-d');
        $this->selectedWeek = (int) $now->weekOfYear;
        $this->selectedWeekYear = $now->year;
        $this->selectedMonth = $now->month;
        $this->selectedMonthYear = $now->year;
        $this->selectedYear = $now->year;

        $this->applyPeriod();

        // URL validation for page
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }



    public function updatingFilterCashier(): void
    {
        $this->resetPage();
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
        $now = now();

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
                // Keep startDate and endDate as is (set by date picker)
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
        $this->search = '';

        $this->filterCashier = null;
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

    public function view(int $id): void
    {
        $this->selectedTransaction = Transaction::with(['user', 'details.modifiers', 'paymentSource'])->findOrFail($id);
        $this->showModal = true;
    }

    public function printReceipt(int $id): void
    {
        $this->lastTransaction = Transaction::with(['details.product.category', 'details.modifiers', 'paymentSource', 'user'])->find($id);
        if ($this->lastTransaction) {
            $this->showReceiptModal = true;
            $this->dispatch('print-receipt');
        }
    }

    public function closeReceiptModal(): void
    {
        if ($this->lastTransaction) {
            Transaction::where('id', $this->lastTransaction->id)->increment('print_count');
        }
        $this->showReceiptModal = false;
        $this->lastTransaction = null;
    }

    public function reprintOnly(): void
    {
        $this->dispatch('print-receipt');
    }

    public function getExportUrl(): string
    {
        return route('export.transactions', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'search' => $this->search,
            'cashier' => $this->filterCashier,
        ]);
    }

    public function getExportDetailUrl(): string
    {
        return route('export.transactions.detail', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'search' => $this->search,
            'cashier' => $this->filterCashier,
        ]);
    }

    public function getPrintTableUrl(): string
    {
        return route('print.transactions.table', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'search' => $this->search,
            'cashier' => $this->filterCashier,
        ]);
    }

    public function getPrintDetailUrl(): string
    {
        return route('print.transactions.detail', [
            'start' => $this->startDate,
            'end' => $this->endDate,
            'search' => $this->search,
            'cashier' => $this->filterCashier,
        ]);
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

    public function render()
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $transactions = Transaction::with(['user', 'paymentSource'])
            ->whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->when($this->search, fn($q) => $q->where('invoice_number', 'like', "%{$this->search}%"))
            ->when($this->filterCashier, fn($q) => $q->where('user_id', $this->filterCashier))
            ->latest()
            ->paginate(15);

        // Smart pagination
        if ($transactions->lastPage() < $this->getPage() && $transactions->lastPage() > 0) {
            $this->setPage($transactions->lastPage());
            $transactions = Transaction::with(['user', 'paymentSource'])
                ->whereBetween('created_at', [$start, $end])
                ->where('status', 'completed')
                ->when($this->search, fn($q) => $q->where('invoice_number', 'like', "%{$this->search}%"))
                ->when($this->filterCashier, fn($q) => $q->where('user_id', $this->filterCashier))
                ->latest()
                ->paginate(15);
        }

        // Summary stats
        $summary = [
            'total_transactions' => Transaction::whereBetween('created_at', [$start, $end])
                ->where('status', 'completed')
                ->when($this->filterCashier, fn($q) => $q->where('user_id', $this->filterCashier))
                ->count(),
            'total_revenue' => Transaction::whereBetween('created_at', [$start, $end])
                ->where('status', 'completed')
                ->when($this->filterCashier, fn($q) => $q->where('user_id', $this->filterCashier))
                ->sum('total'),
        ];

        $cashiers = User::whereHas('transactions')->orderBy('name')->get(['id', 'name']);

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return view('livewire.admin.transaction-history', compact('transactions', 'summary', 'cashiers', 'months'))
            ->title('Riwayat Transaksi');
    }
}
