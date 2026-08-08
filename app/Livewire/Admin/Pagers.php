<?php

namespace App\Livewire\Admin;

use App\Models\Pager;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Pagers extends Component
{
    use WithPagination;

    public function mount()
    {
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
        }
    }

    public bool $showModal = false;
    public bool $showDeleteModal = false;
    public ?int $editingId = null;
    public ?int $deletingId = null;

    #[Rule('required|min:1|max:20')]
    public string $number = '';

    #[Rule('nullable|max:20')]
    public ?string $docking_number = '';

    public bool $is_active = true;

    #[Url(except: '')]
    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'number', 'docking_number', 'is_active']);
        $this->is_active = true;
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $pager = Pager::findOrFail($id);
        $this->editingId = $pager->id;
        $this->number = $pager->number;
        $this->docking_number = $pager->docking_number ?? '';
        $this->is_active = $pager->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $exceptId = $this->editingId ?: 'NULL';
        $this->validate([
            'number' => "required|min:1|max:20|unique:pagers,number,{$exceptId},id,deleted_at,NULL",
            'docking_number' => 'nullable|max:20',
            'is_active' => 'boolean',
        ]);

        try {
            $data = [
                'number' => $this->number,
                'docking_number' => $this->docking_number ?: null,
                'is_active' => $this->is_active,
            ];

            if ($this->editingId) {
                Pager::find($this->editingId)->update($data);
                $this->dispatch('notify', type: 'success', message: 'Pager berhasil diperbarui');
            } else {
                Pager::create($data);
                $this->dispatch('notify', type: 'success', message: 'Pager berhasil ditambahkan');
            }

            $this->showModal = false;
            $this->reset(['editingId', 'number', 'docking_number', 'is_active']);
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
            Pager::findOrFail($this->deletingId)->delete();
            $this->dispatch('notify', type: 'success', message: 'Pager berhasil dihapus');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Gagal menghapus data: ' . $e->getMessage());
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
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
        $pagers = Pager::getPaginated($this->search, 10);

        if ($pagers->lastPage() < $this->getPage() && $pagers->lastPage() > 0) {
            $this->setPage($pagers->lastPage());
            $pagers = Pager::getPaginated($this->search, 10);
        }

        return view('livewire.admin.pagers', compact('pagers'))
            ->title('Pager');
    }
}
