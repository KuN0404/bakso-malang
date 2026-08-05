<?php

namespace App\Livewire;

use App\Enums\KitchenTarget;
use App\Models\Shift;
use App\Models\TransactionDetail;
use Livewire\Component;
use Livewire\Attributes\On;

class KitchenDisplay extends Component
{
    public string $activeTab = 'food';

    public function mount(): void
    {
        $sessionTab = session('kitchen_active_tab');
        if (in_array($sessionTab, [KitchenTarget::FOOD->value, KitchenTarget::DRINK->value])) {
            $this->activeTab = $sessionTab;
        }
    }

    #[On('order-created')]
    public function refreshOrders(): void
    {
        // Livewire auto-refreshes
    }

    public function setTab(string $tab): void
    {
        if (in_array($tab, [KitchenTarget::FOOD->value, KitchenTarget::DRINK->value])) {
            $this->activeTab = $tab;
            session(['kitchen_active_tab' => $tab]);
        }
    }

    public function markAsDone(int $detailId): void
    {
        $this->authorize('update_order_status');

        $detail = TransactionDetail::find($detailId);
        if ($detail) {
            $detail->markAsDone();
            $this->dispatch('play-sound', 'done');
        }
    }

    public function render()
    {
        $openShiftIds = Shift::getOpenShiftIds();
        $openShifts   = Shift::getOpenShifts();

        $foodItems  = TransactionDetail::getKitchenQueue($openShiftIds, KitchenTarget::FOOD);
        $drinkItems = TransactionDetail::getKitchenQueue($openShiftIds, KitchenTarget::DRINK);

        $activeItems = $this->activeTab === KitchenTarget::DRINK->value ? $drinkItems : $foodItems;
        $groupedOrders = $activeItems->groupBy('transaction_id');

        return view('livewire.kitchen-display', [
            'orders'          => $groupedOrders,
            'openShifts'      => $openShifts,
            'openShiftsCount' => $openShifts->count(),
            'foodCount'       => $foodItems->count(),
            'drinkCount'      => $drinkItems->count(),
        ])->layout('layouts.pos');
    }
}
