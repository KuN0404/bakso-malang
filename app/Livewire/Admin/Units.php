<?php

namespace App\Livewire\Admin;

use App\Models\Unit;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Units extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:2|max:50')]
    public string $name = '';

    #[Rule('required|max:10')]
    public string $symbol = '';

    public string $group = 'Berat';

    public int $sort_order = 0;

    public bool $is_active = true;

    public string $search = '';

    public array $groupOptions = ['Berat', 'Volume / Cairan', 'Satuan / Jumlah', 'Kemasan'];

    public ?int $cachedLastOrder = null;

    public function getLastSortOrderProperty(): int
    {
        if ($this->cachedLastOrder === null) {
            $this->cachedLastOrder = Unit::getMaxSortOrder();
        }
        return $this->cachedLastOrder;
    }

    public function refreshOrderCache(): void
    {
        $this->cachedLastOrder = null;
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'symbol', 'group', 'sort_order', 'is_active']);
        $this->group = 'Berat';
        $this->is_active = true;
        $this->sort_order = $this->lastSortOrder + 1;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $unit = Unit::findOrFail($id);
        $this->editingId = $unit->id;
        $this->name = $unit->name;
        $this->symbol = $unit->symbol;
        $this->group = $unit->group;
        $this->sort_order = $unit->sort_order;
        $this->is_active = $unit->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit_units' : 'create_units');

        $exceptId = $this->editingId ?: 'NULL';
        $this->validate([
            'name' => 'required|min:2|max:50',
            'symbol' => "required|max:10|unique:units,symbol,{$exceptId},id,deleted_at,NULL",
            'group' => 'required|in:Berat,Volume / Cairan,Satuan / Jumlah,Kemasan',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'symbol' => $this->symbol,
            'group' => $this->group,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Unit::find($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Satuan berhasil diperbarui');
        } else {
            Unit::create($data);
            $this->dispatch('notify', type: 'success', message: 'Satuan berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'symbol', 'group', 'sort_order', 'is_active']);
        $this->refreshOrderCache();
    }

    public function delete(int $id): void
    {
        $this->authorize('delete_units');

        $unit = Unit::findOrFail($id);

        if ($unit->ingredients()->count() > 0 || $unit->components()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Satuan tidak dapat dihapus karena masih dipakai oleh bahan baku atau komponen');
            return;
        }

        $unit->delete();
        $this->dispatch('notify', type: 'success', message: 'Satuan berhasil dihapus');
        $this->refreshOrderCache();
    }

    public function render()
    {
        $units = Unit::getPaginated($this->search, 10);

        return view('livewire.admin.units', compact('units'))
            ->title('Satuan');
    }
}
