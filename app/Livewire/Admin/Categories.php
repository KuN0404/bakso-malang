<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Computed;
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

    public int $sort_order = 0;

    public bool $is_active = true;

    public string $search = '';

    // Cache lastSortOrder to avoid repeated queries
    public ?int $cachedLastOrder = null;

    public function getLastSortOrderProperty(): int
    {
        if ($this->cachedLastOrder === null) {
            $this->cachedLastOrder = Category::getMaxSortOrder();
        }
        return $this->cachedLastOrder;
    }
    
    // Reset cache when data changes
    public function refreshOrderCache(): void
    {
        $this->cachedLastOrder = null;
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'description', 'icon', 'sort_order', 'is_active']);
        $this->icon = 'folder';
        $this->is_active = true;
        // Auto-fill with next sort order
        $this->sort_order = $this->lastSortOrder + 1;
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
        $this->authorize($this->editingId ? 'edit_categories' : 'create_categories');

        // Custom validation for unique sort_order
        $rules = [
            'name' => 'required|min:2|max:100',
            'description' => 'nullable|max:500',
            'icon' => 'nullable|max:50',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ];
        
        $this->validate($rules);
        
        // Check unique sort_order (except current editing)
        $existingOrder = Category::isSortOrderExists($this->sort_order, $this->editingId);
            
        if ($existingOrder) {
            $this->addError('sort_order', 'Urutan ini sudah digunakan oleh kategori lain.');
            return;
        }

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
        $this->refreshOrderCache();
    }

    public function delete(int $id): void
    {
        $this->authorize('delete_categories');

        $category = Category::findOrFail($id);

        if ($category->products()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Kategori tidak dapat dihapus karena memiliki produk');
            return;
        }

        $category->delete();
        $this->dispatch('notify', type: 'success', message: 'Kategori berhasil dihapus');
        $this->refreshOrderCache();
    }

    public function render()
    {
        $categories = Category::getPaginated($this->search, 10);

        return view('livewire.admin.categories', compact('categories'))
            ->title('Kategori');
    }
}
