<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Cache;

#[Layout('layouts.customer')]
class CustomerDisplay extends Component
{
    public array $cart = [];
    public float $subtotal = 0;
    public float $taxAmount = 0;
    public float $total = 0;
    public float $paidAmount = 0;
    public float $changeAmount = 0;
    public string $customerName = '';
    public string $cashierName = '';
    
    // Polling interval
    protected $listeners = ['refreshDisplay' => '$refresh'];

    public function render()
    {
        $this->fetchState();
        return view('livewire.customer-display');
    }

    public function fetchState()
    {
        // Use auth()->id() to fetch the specific cashier's cart
        $userId = auth()->id();
        
        if ($userId) {
            $data = Cache::get('pos_active_cart_' . $userId, []);
            
            if (!empty($data)) {
                $this->cart = $data['cart'] ?? [];
                $this->subtotal = $data['subtotal'] ?? 0;
                $this->taxAmount = $data['tax_amount'] ?? 0;
                $this->total = $data['total'] ?? 0;
                $this->paidAmount = $data['paid_amount'] ?? 0;
                $this->changeAmount = $data['change_amount'] ?? 0;
                $this->customerName = $data['customer_name'] ?? '';
                $this->cashierName = $data['cashier_name'] ?? '';
            } else {
                $this->resetState();
            }
        }
    }

    private function resetState()
    {
        $this->cart = [];
        $this->subtotal = 0;
        $this->taxAmount = 0;
        $this->total = 0;
        $this->paidAmount = 0;
        $this->changeAmount = 0;
        $this->customerName = '';
    }
}
