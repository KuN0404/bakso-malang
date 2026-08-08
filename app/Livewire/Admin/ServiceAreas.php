<?php

namespace App\Livewire\Admin;

use App\Models\ServiceArea;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ServiceAreas extends Component
{
    use WithPagination;

    public function mount()
    {
        // Sanitize Page Parameter
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }

        // Validate Filter Type from URL
        if ($this->filterType && !array_key_exists($this->filterType, ServiceArea::TYPE_LABELS)) {
            $this->filterType = '';
        }
    }

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Rule('required|min:2|max:50')]
    public string $name = '';

    #[Rule('required|min:1|max:20')]
    public string $code = '';

    #[Rule('nullable|max:255')]
    public ?string $description = '';

    #[Rule('required|in:table,room,zone,other')]
    public string $type = 'table';

    #[Rule('required|integer|min:1|max:100')]
    public int $capacity = 4;

    #[Rule('required|integer|min:0')]
    public int $sort_order = 0;

    public bool $is_active = true;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterType = '';

    // Cache lastSortOrder
    public ?int $cachedLastOrder = null;

    public function getLastSortOrderProperty(): int
    {
        if ($this->cachedLastOrder === null) {
            $this->cachedLastOrder = ServiceArea::getMaxSortOrder();
        }
        return $this->cachedLastOrder;
    }

    public function refreshOrderCache(): void
    {
        $this->cachedLastOrder = null;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'code', 'description', 'type', 'capacity', 'sort_order', 'is_active']);
        $this->type = 'table';
        $this->capacity = 4;
        $this->is_active = true;
        $this->sort_order = $this->lastSortOrder + 1;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $area = ServiceArea::findOrFail($id);
        $this->editingId = $area->id;
        $this->name = $area->name;
        $this->code = $area->code;
        $this->description = $area->description ?? '';
        $this->type = $area->type;
        $this->capacity = $area->capacity;
        $this->sort_order = $area->sort_order;
        $this->is_active = $area->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $exceptId = $this->editingId ?: 'NULL';
        $this->validate([
            'name' => 'required|min:2|max:50',
            'code' => "required|min:1|max:20|unique:service_areas,code,{$exceptId},id,deleted_at,NULL",
            'description' => 'nullable|max:255',
            'type' => 'required|in:table,room,zone,other',
            'capacity' => 'required|integer|min:1|max:100',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $data = [
                'name' => $this->name,
                'code' => strtoupper($this->code),
                'description' => $this->description ?: null,
                'type' => $this->type,
                'capacity' => $this->capacity,
                'sort_order' => $this->sort_order,
                'is_active' => $this->is_active,
            ];

            if ($this->editingId) {
                ServiceArea::find($this->editingId)->update($data);
                $this->dispatch('notify', type: 'success', message: 'Area pelayanan berhasil diperbarui');
            } else {
                ServiceArea::create($data);
                $this->dispatch('notify', type: 'success', message: 'Area pelayanan berhasil ditambahkan');
            }

            $this->showModal = false;
            $this->reset(['editingId', 'name', 'code', 'description', 'type', 'capacity', 'sort_order', 'is_active']);
            $this->refreshOrderCache();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menyimpan data: ' . $e->getMessage());
        }
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if (!$this->deletingId) return;

        try {
            $area = ServiceArea::findOrFail($this->deletingId);
            
            // Check if area has completed transactions
            if ($area->hasCompletedTransactions()) {
                $this->dispatch('notify', type: 'error', message: 'Area tidak dapat dihapus karena memiliki transaksi');
                $this->showDeleteModal = false;
                $this->deletingId = null;
                return;
            }

            $area->delete();
            $this->dispatch('notify', type: 'success', message: 'Area pelayanan berhasil dihapus');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menghapus data: ' . $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->refreshOrderCache();
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function render()
    {
        $areas = ServiceArea::getPaginated($this->search, $this->filterType, 10);

        // Smart Redirect for Pagination
        if ($areas->lastPage() < $this->getPage() && $areas->lastPage() > 0) {
            $this->setPage($areas->lastPage());
            $areas = ServiceArea::getPaginated($this->search, $this->filterType, 10);
        }

        $typeLabels = ServiceArea::TYPE_LABELS;

        return view('livewire.admin.service-areas', compact('areas', 'typeLabels'))
            ->title('Area Pelayanan');
    }
}
