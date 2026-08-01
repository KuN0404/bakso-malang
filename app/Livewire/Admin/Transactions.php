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
        $this->selectedTransaction = Transaction::getForDetail($id);
        $this->showModal = true;
    }

    public function cancel(int $id): void
    {
        $transaction = Transaction::findOrFail($id);
        
        if ($transaction->status === 'cancelled') {
            $this->dispatch('notify', type: 'error', message: 'Transaksi sudah dibatalkan');
            return;
        }

        $transaction->cancel(0, 'Dibatalkan oleh admin');
        $this->dispatch('notify', type: 'success', message: 'Transaksi berhasil dibatalkan');
    }

    public function render()
    {
        $transactions = Transaction::getPaginatedForAdminList(
            $this->search,
            $this->filterStatus,
            $this->filterDate,
            15
        );

        return view('livewire.admin.transactions', compact('transactions'))
            ->title('Transaksi');
    }
}
