<?php

namespace App\Livewire\SelfOrder;

use App\Models\SelfOrder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Status Pesanan')]
class SelfOrderTracking extends Component
{
    #[Locked]
    public string $token;

    public ?SelfOrder $selfOrder = null;

    // Auto-refresh setiap 5 detik untuk update status
    public int $refreshInterval = 10000;

    public function mount(string $token): void
    {
        $this->token     = $token;
        $this->selfOrder = SelfOrder::findByToken($token);

        if (!$this->selfOrder) {
            abort(404, 'Order tidak ditemukan.');
        }
    }

    /**
     * Polling refresh — update status dari database.
     */
    public function refreshStatus(): void
    {
        if ($this->selfOrder) {
            $this->selfOrder->refresh();

            // Stop polling jika status final
            if ($this->selfOrder->status->isFinal()) {
                $this->refreshInterval = 0;
            }
        }
    }

    #[Computed]
    public function statusSteps(): array
    {
        $steps = [
            ['key' => 'placed',     'label' => 'Pesanan Diterima', 'icon' => 'check'],
            ['key' => 'paid',       'label' => 'Pembayaran Berhasil', 'icon' => 'currency-dollar'],
            ['key' => 'processing', 'label' => 'Sedang Diproses', 'icon' => 'fire'],
            ['key' => 'ready',      'label' => 'Siap Diambil', 'icon' => 'bell'],
            ['key' => 'completed',  'label' => 'Pesanan Selesai', 'icon' => 'badge-check'],
        ];

        $currentStatus = $this->selfOrder?->status?->value ?? '';

        $activeIndex = match ($currentStatus) {
            'pending_payment', 'waiting_payment' => 0,
            'paid'                               => 1,
            'processing'                         => 2,
            'ready'                              => 3,
            'completed'                          => 4,
            default                              => 0,
        };

        foreach ($steps as $i => &$step) {
            if ($currentStatus === 'completed') {
                $step['done']   = true;
                $step['active'] = false;
            } else {
                $step['done']   = $i < $activeIndex;
                $step['active'] = $i === $activeIndex;
            }
        }

        return $steps;
    }

    public function render()
    {
        return view('livewire.self-order.self-order-tracking')
            ->layout('layouts.self-order');
    }
}
