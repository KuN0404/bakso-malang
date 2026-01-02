<?php

namespace App\Livewire\Admin;

use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ProductDetail extends Component
{
    use WithPagination;

    public Product $product;

    public function mount(Product $product)
    {
        $this->product = $product->load(['category', 'modifierGroups', 'activities.causer']);
    }

    public function render()
    {
        return view('livewire.admin.product-detail', [
            'stockLogs' => $this->product->stockLogs()->with('user')->latest()->paginate(10)
        ])->title('Detail Produk: ' . $this->product->name);
    }
}
