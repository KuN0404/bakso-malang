<?php

namespace App\Livewire\Admin;

use App\Models\Shift;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ShiftReport extends Component
{
    use WithPagination;

    public string $filterDate = '';
    public ?int $filterUserId = null;
    public ?Shift $selectedShift = null;
    public bool $showDetailModal = false;

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    public function viewDetail(int $id): void
    {
        $this->selectedShift = Shift::with(['user', 'transactions', 'expenses'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $shifts = Shift::with(['user'])
            ->when($this->filterDate, fn($q) => $q->whereDate('started_at', $this->filterDate))
            ->when($this->filterUserId, fn($q) => $q->where('user_id', $this->filterUserId))
            ->latest('started_at')
            ->paginate(15);

        // Summary stats
        $todayShifts = Shift::whereDate('started_at', $this->filterDate)->get();
        $totalSales = $todayShifts->sum(fn($s) => $s->transactions->where('status', 'completed')->sum('total'));
        $totalExpenses = $todayShifts->sum(fn($s) => $s->expenses->sum('amount'));
        $totalDifference = $todayShifts->sum('cash_difference');

        $users = \App\Models\User::orderBy('name')->get();

        return view('livewire.admin.shift-report', compact('shifts', 'users', 'totalSales', 'totalExpenses', 'totalDifference'))
            ->title('Laporan Shift');
    }
}
