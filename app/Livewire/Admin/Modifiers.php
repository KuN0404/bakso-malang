<?php

namespace App\Livewire\Admin;

use App\Models\Modifier;
use App\Models\ModifierGroup;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Modifiers extends Component
{
    use WithPagination;

    public bool $showGroupModal = false;
    public bool $showModifierModal = false;
    public ?int $editingGroupId = null;
    public ?int $editingModifierId = null;
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
    public float $priceAdjustment = 0;
    public bool $modifierIsActive = true;

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
        $this->reset(['editingModifierId', 'modifierName', 'priceAdjustment', 'modifierIsActive']);
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

    public function render()
    {
        $groups = ModifierGroup::withCount('modifiers')->get();
        $modifiers = $this->selectedGroupId 
            ? Modifier::where('modifier_group_id', $this->selectedGroupId)->get()
            : collect();
        $selectedGroup = $this->selectedGroupId ? ModifierGroup::find($this->selectedGroupId) : null;

        return view('livewire.admin.modifiers', compact('groups', 'modifiers', 'selectedGroup'))
            ->title('Modifier');
    }
}
