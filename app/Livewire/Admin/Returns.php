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

    public string $search = '';
    public string $filterDate = '';
    
    // Detail
    public bool $showDetailModal = false;
    public ?ProductReturn $selectedReturn = null;

    public function mount(): void
    {
        $this->filterDate = now()->format('Y-m-d');
    }

    // Reset pagination when search changes
    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function viewDetail(int $id): void
    {
        $this->selectedReturn = ProductReturn::with(['transaction', 'items', 'user'])->findOrFail($id);
        $this->showDetailModal = true;
    }

    public function render()
    {
        $returns = ProductReturn::with(['transaction', 'user'])
            ->when($this->filterDate, fn($q) => $q->whereDate('created_at', $this->filterDate))
            ->when($this->search, fn($q) => $q->where('return_number', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(15);

        $todayTotal = ProductReturn::whereDate('created_at', $this->filterDate)->sum('total_refund');

        return view('livewire.admin.returns', compact('returns', 'todayTotal'))
            ->title('Laporan Retur');
    }
}
