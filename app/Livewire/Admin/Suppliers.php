<?php

namespace App\Livewire\Admin;

use App\Models\Supplier;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Suppliers extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:2|max:150')]
    public string $name = '';

    #[Rule('nullable|max:30')]
    public ?string $phone = '';

    #[Rule('nullable|max:500')]
    public ?string $address = '';

    #[Rule('nullable|max:500')]
    public ?string $note = '';

    public bool $is_active = true;

    public string $search = '';

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'phone', 'address', 'note', 'is_active']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->editingId = $supplier->id;
        $this->name = $supplier->name;
        $this->phone = $supplier->phone ?? '';
        $this->address = $supplier->address ?? '';
        $this->note = $supplier->note ?? '';
        $this->is_active = $supplier->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit_suppliers' : 'create_suppliers');

        $exceptId = $this->editingId ?: 'NULL';
        $this->validate([
            'name' => "required|min:2|max:150|unique:suppliers,name,{$exceptId},id,deleted_at,NULL",
            'phone' => 'nullable|max:30',
            'address' => 'nullable|max:500',
            'note' => 'nullable|max:500',
            'is_active' => 'boolean',
        ]);

        $data = [
            'name' => $this->name,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'note' => $this->note ?: null,
            'is_active' => $this->is_active,
        ];

        if ($this->editingId) {
            Supplier::find($this->editingId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Supplier berhasil diperbarui');
        } else {
            Supplier::create($data);
            $this->dispatch('notify', type: 'success', message: 'Supplier berhasil ditambahkan');
        }

        $this->showModal = false;
        $this->reset(['editingId', 'name', 'phone', 'address', 'note', 'is_active']);
    }

    public function delete(int $id): void
    {
        $this->authorize('delete_suppliers');

        $supplier = Supplier::findOrFail($id);

        if ($supplier->purchases()->count() > 0) {
            $this->dispatch('notify', type: 'error', message: 'Supplier tidak dapat dihapus karena memiliki riwayat pembelian');
            return;
        }

        $supplier->delete();
        $this->dispatch('notify', type: 'success', message: 'Supplier berhasil dihapus');
    }

    public function render()
    {
        $suppliers = Supplier::getPaginated($this->search, 10);

        return view('livewire.admin.suppliers', compact('suppliers'))
            ->title('Supplier');
    }
}
