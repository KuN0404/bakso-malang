<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use Livewire\Component;
use Livewire\WithPagination;

class MenuCatalog extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCategory = null;
    public $selectedProduct = null;
    public $showDetailModal = false;

    public function selectCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function showProductDetail($id)
    {
        $this->selectedProduct = Product::with(['category', 'modifierGroups.activeModifiers'])->find($id);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedProduct = null;
    }

    public function render()
    {
        $categories = Category::active()->withCount('products')->get();
        $totalProductsCount = Product::count();
        $activeProductsCount = Product::where('is_active', true)->count();

        $query = Product::with(['category', 'modifierGroups'])
            ->when($this->selectedCategory, function ($q) {
                $q->where('category_id', $this->selectedCategory);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->latest();

        $products = $query->paginate(12);

        return view('livewire.admin.menu-catalog', [
            'categories' => $categories,
            'products' => $products,
            'totalProductsCount' => $totalProductsCount,
            'activeProductsCount' => $activeProductsCount,
        ])->layout('layouts.admin', ['title' => 'Katalog Menu']);
    }
}
