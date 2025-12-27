<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\ModifierGroup;
use App\Models\Product;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Products extends Component
{
    use WithPagination, WithFileUploads;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|exists:categories,id')]
    public ?int $category_id = null;

    #[Rule('required|min:2|max:150')]
    public string $name = '';

    #[Rule('required|max:50')]
    public string $sku = '';

    #[Rule('nullable|max:500')]
    public ?string $description = '';

    #[Rule('required|numeric|min:0')]
    public float $price = 0;

    #[Rule('nullable|numeric|min:0')]
    public float $cost_price = 0;

    #[Rule('nullable|image|max:2048')]
    public $image;

    public ?string $existingImage = null;

    public bool $is_active = true;
    public bool $is_featured = false;
    public bool $track_stock = false;
    public int $stock = 0;

    public array $selectedModifierGroups = [];

    public string $search = '';
    public ?int $filterCategory = null;

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $product = Product::with('modifierGroups')->findOrFail($id);
        $this->editingId = $product->id;
        $this->category_id = $product->category_id;
        $this->name = $product->name;
        $this->sku = $product->sku;
        $this->description = $product->description ?? '';
        $this->price = $product->price;
        $this->cost_price = $product->cost_price;
        $this->existingImage = $product->image;
        $this->is_active = $product->is_active;
        $this->is_featured = $product->is_featured;
        $this->track_stock = $product->track_stock;
        $this->stock = $product->stock;
        $this->selectedModifierGroups = $product->modifierGroups->pluck('id')->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'category_id' => $this->category_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'description' => $this->description ?: null,
            'price' => $this->price,
            'cost_price' => $this->cost_price,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'track_stock' => $this->track_stock,
            'stock' => $this->stock,
        ];

        if ($this->image) {
            $data['image'] = $this->image->store('products', 'public');
        }

        if ($this->editingId) {
            $product = Product::find($this->editingId);
            $product->update($data);
            $product->modifierGroups()->sync($this->selectedModifierGroups);
            $this->dispatch('notify', type: 'success', message: 'Produk berhasil diperbarui');
        } else {
            $product = Product::create($data);
            $product->modifierGroups()->sync($this->selectedModifierGroups);
            $this->dispatch('notify', type: 'success', message: 'Produk berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'sku', 'description', 'price', 'cost_price', 'image', 'existingImage', 'is_active', 'is_featured', 'track_stock', 'stock', 'category_id', 'selectedModifierGroups']);
    }

    public function delete(int $id): void
    {
        Product::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Produk berhasil dihapus');
    }

    public function render()
    {
        $products = Product::with('category')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('sku', 'like', "%{$this->search}%"))
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->latest()
            ->paginate(10);

        $categories = Category::active()->ordered()->get();
        $modifierGroups = ModifierGroup::active()->get();

        return view('livewire.admin.products', compact('products', 'categories', 'modifierGroups'))
            ->title('Produk');
    }
}
