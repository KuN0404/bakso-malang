<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Livewire\Component;

class LandingPage extends Component
{
    public $selectedCategory = null;
    public $selectedProduct = null;
    public $showProductModal = false;

    public function selectCategory($categoryId = null)
    {
        $this->selectedCategory = $categoryId;
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
        $storeName = Setting::get('store_name', 'Bakso Malang', 'general');
        $logoWeb = Setting::get('logo_web', null, 'general');
        $siteLogo = Setting::get('site_logo', null, 'general');
        $storeAddress = Setting::get('store_address', 'Jl. Raya Bakso Malang No. 123, Kota Malang', 'general');
        $storePhone = Setting::get('store_phone', '0812-3456-7890', 'general');
        $storeEmail = Setting::get('store_email', 'info@baksomalang.com', 'general');
        $storeHours = Setting::get('store_hours', 'Setiap Hari: 09.00 - 21.00 WIB', 'general');

        $categories = Category::active()->withCount(['products' => function ($q) {
            $q->where('is_active', true);
        }])->get();

        $featuredProducts = Product::where('is_active', true)
            ->where('is_featured', true)
            ->with(['category'])
            ->take(6)
            ->get();

        $menuProducts = Product::where('is_active', true)
            ->with(['category', 'modifierGroups.activeModifiers'])
            ->when($this->selectedCategory, function ($q) {
                $q->where('category_id', $this->selectedCategory);
            })
            ->take(8)
            ->get();

        return view('livewire.landing-page', [
            'storeName' => $storeName,
            'logoWeb' => $logoWeb,
            'siteLogo' => $siteLogo,
            'storeAddress' => $storeAddress,
            'storePhone' => $storePhone,
            'storeEmail' => $storeEmail,
            'storeHours' => $storeHours,
            'categories' => $categories,
            'featuredProducts' => $featuredProducts,
            'menuProducts' => $menuProducts,
        ])->layout('layouts.customer', ['title' => $storeName . ' - Cita Rasa Otentik Bakso Malang']);
    }
}
