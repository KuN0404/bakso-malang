<?php

namespace App\Livewire\Admin;

use App\Models\Shift;
use App\Models\ShiftExpense;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Shifts extends Component
{
    use WithPagination;

    public ?Shift $selectedShift = null;
    public bool $showDetailModal = false;

    public function viewDetail(int $id): void
    {
        $shift = Shift::with(['user', 'transactions', 'expenses'])->findOrFail($id);
        
        // Check permission
        if (!auth()->user()->can('view_all_shifts') && $shift->user_id !== auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Tidak memiliki akses');
            return;
        }
        
        $this->selectedShift = $shift;
        $this->showDetailModal = true;
    }

    public function render()
    {
        // Permission-based query
        $query = Shift::with(['user'])
            ->whereDate('started_at', today()) // Only today's shifts
            ->latest('started_at');

        // If user can't view all shifts, only show their own
        if (!auth()->user()->can('view_all_shifts')) {
            $query->where('user_id', auth()->id());
        }

        $shifts = $query->get();

        // Calculate today's stats
        $totalSales = $shifts->sum(function ($s) {
            return $s->transactions->where('status', 'completed')->sum('total');
        });
        $totalExpenses = $shifts->sum(function ($s) {
            return $s->expenses->sum('amount');
        });

        return view('livewire.admin.shifts', compact('shifts', 'totalSales', 'totalExpenses'))
            ->title('Shift Hari Ini');
    }
}
