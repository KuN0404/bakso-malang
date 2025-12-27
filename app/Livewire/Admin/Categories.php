<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Categories extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:2|max:100')]
    public string $name = '';

    #[Rule('nullable|max:500')]
    public ?string $description = '';

    #[Rule('nullable|max:50')]
    public string $icon = 'folder';

    #[Rule('integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = true;

    public string $search = '';

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description', 'icon', 'sort_order', 'is_active']);
        $this->icon = 'folder';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->editingId = $category->id;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->icon = $category->icon ?? 'folder';
        $this->sort_order = $category->sort_order;
        $this->is_active = $category->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description ?: null,
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Category::find($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Kategori berhasil diperbarui');
        } else {
            Category::create($data);
            $this->dispatch('notify', type: 'success', message: 'Kategori berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'description', 'icon', 'sort_order', 'is_active']);
    }

    public function delete(int $id): void
    {
        $category = Category::findOrFail($id);
        
        if ($category->products()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Kategori tidak dapat dihapus karena memiliki produk');
            return;
        }

        $category->delete();
        $this->dispatch('notify', type: 'success', message: 'Kategori berhasil dihapus');
    }

    public function render()
    {
        $categories = Category::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->withCount('products')
            ->orderBy('sort_order')
            ->paginate(10);

        return view('livewire.admin.categories', compact('categories'))
            ->title('Kategori');
    }
}
