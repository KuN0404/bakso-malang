<?php

namespace App\Livewire\Admin;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class PurchaseDetail extends Component
{
    public Purchase $purchase;

    public function mount(Purchase $purchase)
    {
        $this->purchase = $purchase->load(['items.ingredient.unit', 'items.product', 'user', 'supplier']);

        // Toast dari halaman lain (mis. setelah redirect dari form Tambah Pembelian) —
        // dikirim lewat session flash karena redirect antar-halaman membuang event
        // browser yang di-dispatch pada request sebelumnya.
        if ($flash = session('notify')) {
            $this->dispatch('notify', type: $flash['type'], message: $flash['message']);
        }
    }

    public function render()
    {
        return view('livewire.admin.purchase-detail')
            ->title('Detail Pembelian: ' . $this->purchase->invoice_number);
    }
}
