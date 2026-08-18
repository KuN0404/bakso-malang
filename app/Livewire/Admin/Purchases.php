<?php

namespace App\Livewire\Admin;

use App\Models\Purchase;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Purchases extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public int $perPage = 10;

    public function render()
    {
        return view('livewire.admin.purchases', [
            'purchases' => Purchase::getPaginated($this->search, $this->perPage),
        ])->title('Pembelian Stok');
    }
}
