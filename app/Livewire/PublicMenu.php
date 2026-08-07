<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Livewire\Component;
use Livewire\WithPagination;

class PublicMenu extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedCategory = null;
    public $selectedProduct = null;
    public $showProductModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'selectedCategory' => ['except' => null],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function selectCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
        $this->resetPage();
    }

    public function openProductModal($productId)
    {
        $this->selectedProduct = Product::with(['category', 'modifierGroups.activeModifiers'])->find($productId);
        $this->showProductModal = true;
    }

    public function closeProductModal()
    {
        $this->showProductModal = false;
        $this->selectedProduct = null;
    }

    public function render()
    {
        $categories = Category::active()->withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        $query = Product::where('is_active', true)
            ->with(['category', 'modifierGroups.activeModifiers'])
            ->when($this->selectedCategory, function ($q) {
                $q->where('category_id', $this->selectedCategory);
            })
            ->when($this->search, function ($q) {
                $q->where(function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('is_featured', 'desc')
            ->orderBy('name', 'asc');

        $products = $query->paginate(12);

        $storeName = Setting::get('store_name', 'Bakso Malang', 'general');
        $logoWeb = Setting::get('logo_web', null, 'general');
        $logoFull = Setting::get('logo_full', null, 'general');
        $logoType = Setting::get('logo_type', 'single', 'general');

        return view('livewire.public-menu', [
            'categories' => $categories,
            'products' => $products,
            'storeName' => $storeName,
            'logoWeb' => $logoWeb,
            'logoFull' => $logoFull,
            'logoType' => $logoType,
        ])->layout('layouts.customer', ['title' => 'Daftar Menu - ' . $storeName]);
    }
}
