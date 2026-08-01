<?php

namespace App\Livewire\Admin;

use App\Models\Component as ComponentModel;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url; // Added import

#[Layout('layouts.admin')]
class Modifiers extends Component
{
    use WithPagination;

    public function mount()
    {
        // Validation: Page must be numeric and >= 1
        $page = request()->query('page');
        if ($page && (!is_numeric($page) || $page < 1)) {
            $this->setPage(1);
            // Optional: force redirect to clean URL if needed, but setPage is usually enough for Livewire state
        }

        // Validation: selectedGroupId must be numeric and > 0
        // If empty string "" or non-numeric, reset to null immediately
        if ($this->selectedGroupId !== null && (!is_numeric($this->selectedGroupId) || $this->selectedGroupId < 1)) {
            $this->selectedGroupId = null;
        }

        // Validation: If selectedGroupId from URL is invalid (not in DB), reset it and go to page 1
        if ($this->selectedGroupId) {
            $exists = ModifierGroup::existsById($this->selectedGroupId);
            if (!$exists) {
                $this->selectedGroupId = null;
                $this->resetPage(); // Back to page 1
            }
        }
    }

    public bool $showGroupModal = false;
    public bool $showModifierModal = false;
    public ?int $editingGroupId = null;
    public ?int $editingModifierId = null;

    #[Url(except: null)]
    public ?int $selectedGroupId = null;

    // Group fields
    #[Rule('required|min:2|max:100')]
    public string $groupName = '';
    public string $groupSelectionType = 'single';
    public bool $groupIsRequired = false;
    public bool $groupIsActive = true;

    // Modifier fields
    #[Rule('required|min:2|max:100')]
    public string $modifierName = '';

    #[Rule('numeric|min:0')]
    public float $priceAdjustment = 0;

    public bool $modifierIsActive = true;

    public ?int $modifierComponentId = null;

    public function createGroup(): void
    {
        $this->reset(['editingGroupId', 'groupName', 'groupSelectionType', 'groupIsRequired', 'groupIsActive']);
        $this->groupIsActive = true;
        $this->showGroupModal = true;
    }

    public function editGroup(int $id): void
    {
        $group = ModifierGroup::findOrFail($id);
        $this->editingGroupId = $group->id;
        $this->groupName = $group->name;
        $this->groupSelectionType = $group->selection_type;
        $this->groupIsRequired = $group->is_required;
        $this->groupIsActive = $group->is_active;
        $this->showGroupModal = true;
    }

    public function saveGroup(): void
    {
        $this->validate(['groupName' => 'required|min:2|max:100']);

        $data = [
            'name' => $this->groupName,
            'selection_type' => $this->groupSelectionType,
            'is_required' => $this->groupIsRequired,
            'is_active' => $this->groupIsActive,
        ];

        if ($this->editingGroupId) {
            ModifierGroup::find($this->editingGroupId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Grup modifier berhasil diperbarui');
        } else {
            ModifierGroup::create($data);
            $this->dispatch('notify', type: 'success', message: 'Grup modifier berhasil ditambahkan');
        }

        $this->showGroupModal = false;
    }

    public function deleteGroup(int $id): void
    {
        ModifierGroup::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Grup modifier berhasil dihapus');
    }

    public function selectGroup(int $id): void
    {
        $this->selectedGroupId = $id;
    }

    public function createModifier(): void
    {
        if (!$this->selectedGroupId) {
            $this->dispatch('notify', type: 'error', message: 'Pilih grup terlebih dahulu');
            return;
        }
        $this->reset(['editingModifierId', 'modifierName', 'priceAdjustment', 'modifierIsActive', 'modifierComponentId']);
        $this->modifierIsActive = true;
        $this->showModifierModal = true;
    }

    public function editModifier(int $id): void
    {
        $modifier = Modifier::findOrFail($id);
        $this->editingModifierId = $modifier->id;
        $this->modifierName = $modifier->name;
        $this->priceAdjustment = $modifier->price_adjustment;
        $this->modifierIsActive = $modifier->is_active;
        $this->modifierComponentId = $modifier->component_id;
        $this->showModifierModal = true;
    }

    public function saveModifier(): void
    {
        $this->validate(['modifierName' => 'required|min:2|max:100']);

        $data = [
            'modifier_group_id' => $this->selectedGroupId,
            'name' => $this->modifierName,
            'price_adjustment' => $this->priceAdjustment,
            'is_active' => $this->modifierIsActive,
            'component_id' => $this->modifierComponentId ?: null,
        ];

        if ($this->editingModifierId) {
            Modifier::find($this->editingModifierId)->update($data);
            $this->dispatch('notify', type: 'success', message: 'Modifier berhasil diperbarui');
        } else {
            Modifier::create($data);
            $this->dispatch('notify', type: 'success', message: 'Modifier berhasil ditambahkan');
        }

        $this->showModifierModal = false;
    }

    public function deleteModifier(int $id): void
    {
        Modifier::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Modifier berhasil dihapus');
    }

    // Search
    public string $search = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $groups = ModifierGroup::getPaginated($this->search, 10);

        // Smart Redirect: If page > lastPage, redirect to lastPage
        if ($groups->lastPage() < $this->getPage() && $groups->lastPage() > 0) {
            $this->setPage($groups->lastPage());
            // Re-query with correct page
            $groups = ModifierGroup::getPaginated($this->search, 10);
        }
            
        $modifiers = $this->selectedGroupId 
            ? Modifier::getByGroup($this->selectedGroupId)
            : collect();
            
        // Correctness: Must query DB because selected group might not be in current page pagination
        $selectedGroup = $this->selectedGroupId ? ModifierGroup::find($this->selectedGroupId) : null;
        $components = ComponentModel::where('is_active', true)->orderBy('name')->get();

        return view('livewire.admin.modifiers', compact('groups', 'modifiers', 'selectedGroup', 'components'))
            ->title('Modifier');
    }
}
