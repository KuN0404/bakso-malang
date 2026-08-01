<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\TransactionDetail;
use Livewire\Component;
use Livewire\Attributes\On;

class KitchenDisplay extends Component
{
    public $activeTab = 'food'; // food (bakso, mie, tambahan) or drink (minuman)
    public $selectedShiftId = null;

    public function mount()
    {
        $this->selectedShiftId = session('kitchen_selected_shift_id');
    }

    #[On('order-created')] 
    public function refreshOrders()
    {
        // Just refresh the component
    }

    public function selectShift($shiftId)
    {
        $this->selectedShiftId = $shiftId;
        session(['kitchen_selected_shift_id' => $shiftId]);
    }

    public function changeShift()
    {
        $this->selectedShiftId = null;
        session()->forget('kitchen_selected_shift_id');
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function markAsDone($detailId)
    {
        $detail = TransactionDetail::find($detailId);
        if ($detail) {
            $detail->markAsDone();
            $this->dispatch('play-sound', 'done'); // Optional: Sound effect
        }
    }

    public function render()
    {
        // If no shift selected, show selection screen
        if (!$this->selectedShiftId) {
            return view('livewire.kitchen-display', [
                'shifts' => \App\Models\Shift::getOpenShifts()
            ])->layout('layouts.pos');
        }

        // Validate currently selected shift is still OPEN
        $currentShift = \App\Models\Shift::find($this->selectedShiftId);
        if (!$currentShift || $currentShift->status !== 'open') {
            $this->changeShift(); // Reset and clear session
            $this->dispatch('notify', type: 'warning', message: 'Shift kasir telah ditutup.');
            return view('livewire.kitchen-display', [
                'shifts' => \App\Models\Shift::getOpenShifts()
            ])->layout('layouts.pos');
        }

        // Define category slugs for each tab
        $foodSlugs = ['bakso', 'mie', 'tambahan'];
        $drinkSlugs = ['minuman'];

        $targetSlugs = $this->activeTab === 'drink' ? $drinkSlugs : $foodSlugs;

        $items = TransactionDetail::getKitchenQueue($this->selectedShiftId, $targetSlugs)
            ->groupBy('transaction_id'); // Group by Order for "Queue" visualization

        return view('livewire.kitchen-display', [
            'orders' => $items
        ])->layout('layouts.pos'); // Use simple layout or admin layout? User said "role pelayanan", maybe strictly simpler layout? 
        // Let's use layouts.admin for now but maybe full screen mode.
    }
}
