<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Transactions extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?Transaction $selectedTransaction = null;

    public string $search = '';
    public string $filterStatus = '';
    public string $filterDate = '';

    public function view(int $id): void
    {
        $this->selectedTransaction = Transaction::with(['user', 'details.modifiers', 'paymentSource'])->findOrFail($id);
        $this->showModal = true;
    }

    public function cancel(int $id): void
    {
        $transaction = Transaction::findOrFail($id);
        
        if ($transaction->status === 'cancelled') {
            $this->dispatch('notify', type: 'error', message: 'Transaksi sudah dibatalkan');
            return;
        }

        $transaction->cancel('Dibatalkan oleh admin');
        $this->dispatch('notify', type: 'success', message: 'Transaksi berhasil dibatalkan');
    }

    public function render()
    {
        $transactions = Transaction::with(['user', 'paymentSource'])
            ->when($this->search, fn($q) => $q->where('invoice_number', 'like', "%{$this->search}%")->orWhere('customer_name', 'like', "%{$this->search}%"))
            ->when($this->filterStatus, fn($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterDate, fn($q) => $q->whereDate('created_at', $this->filterDate))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.transactions', compact('transactions'))
            ->title('Transaksi');
    }
}
