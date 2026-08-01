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
        $shift = Shift::getForDetail($id);
        
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
        // Permission-based query - only show own shift if user can't view all
        $userId = auth()->user()->can('view_all_shifts') ? null : auth()->id();
        $shifts = Shift::getTodayShiftsForUser($userId);

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
