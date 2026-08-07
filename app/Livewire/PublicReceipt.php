<?php

namespace App\Livewire;

use App\Models\Transaction;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Struk')]
class PublicReceipt extends Component
{
    #[Locked]
    public string $token;

    public ?Transaction $transaction = null;

    public function mount(string $token): void
    {
        $this->token = $token;
        // findByReceiptToken() sudah eager-load semua relasi yang dipakai view
        // (user, paymentSource, serviceArea, pager, details.product, details.modifiers)
        // dalam satu pemanggilan — tidak N+1.
        $this->transaction = Transaction::findByReceiptToken($token);
    }

    public function render()
    {
        return view('livewire.public-receipt')->layout('layouts.self-order');
    }
}
