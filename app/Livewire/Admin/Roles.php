<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use App\Models\Permission;
use App\Models\Role;

#[Layout('layouts.admin')]
class Roles extends Component
{
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:2|max:50')]
    public string $name = '';

    public array $selectedPermissions = [];

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'selectedPermissions']);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $role = Role::getWithPermissions($id);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate(['name' => 'required|min:2|max:50|unique:roles,name,' . $this->editingId]);

        if ($this->editingId) {
            $role = Role::find($this->editingId);
            $role->update(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', type: 'success', message: 'Role berhasil diperbarui');
        } else {
            $role = Role::create(['name' => $this->name, 'guard_name' => 'web']);
            $role->syncPermissions($this->selectedPermissions);
            $this->dispatch('notify', type: 'success', message: 'Role berhasil ditambahkan');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);
        if ($role->name === 'Super Admin') {
            $this->dispatch('notify', type: 'error', message: 'Super Admin tidak dapat dihapus');
            return;
        }
        $role->delete();
        $this->dispatch('notify', type: 'success', message: 'Role berhasil dihapus');
    }

    public function render()
    {
        $roles = Role::getRolesForAdmin();
        $permissions = Permission::getAllSorted();

        // Group permissions using config groups (Indonesian labels)
        $configGroups = config('permissions.groups', []);
        $permissionGroups = [];
        
        foreach ($configGroups as $groupName => $permNames) {
            $groupPerms = $permissions->filter(fn($p) => in_array($p->name, $permNames));
            if ($groupPerms->isNotEmpty()) {
                $permissionGroups[$groupName] = $groupPerms;
            }
        }
        
        // Add any ungrouped permissions to 'Lainnya'
        $allGroupedPerms = collect($configGroups)->flatten()->toArray();
        $ungrouped = $permissions->filter(fn($p) => !in_array($p->name, $allGroupedPerms));
        if ($ungrouped->isNotEmpty()) {
            $permissionGroups['Lainnya'] = $ungrouped;
        }

        return view('livewire.admin.roles', compact('roles', 'permissions', 'permissionGroups'))
            ->title('Role & Permission');
    }
}
