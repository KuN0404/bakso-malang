<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        $role = Role::with('permissions')->findOrFail($id);
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
        $roles = Role::with('permissions')->withCount('users')->get();
        $permissions = Permission::orderBy('name')->get();

        // Group permissions
        $permissionGroups = $permissions->groupBy(function ($perm) {
            return explode('_', $perm->name)[0] ?? 'other';
        });

        return view('livewire.admin.roles', compact('roles', 'permissions', 'permissionGroups'))
            ->title('Role & Permission');
    }
}
