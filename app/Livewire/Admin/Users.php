<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.admin')]
class Users extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    #[Rule('required|min:3|max:50')]
    public string $username = '';

    #[Rule('required|min:2|max:100')]
    public string $name = '';

    #[Rule('required|email|max:100')]
    public string $email = '';

    #[Rule('nullable|min:6')]
    public string $password = '';

    public array $selectedRoles = [];

    public string $search = '';

    public function create(): void
    {
        $this->reset(['editingId', 'username', 'name', 'email', 'password', 'selectedRoles']);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::with('roles')->findOrFail($id);
        $this->editingId = $user->id;
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'username' => 'required|min:3|max:50|unique:users,username,' . $this->editingId,
            'name' => 'required|min:2|max:100',
            'email' => 'required|email|unique:users,email,' . $this->editingId,
        ];
        
        if (!$this->editingId) {
            $rules['password'] = 'required|min:6';
        }
        
        $this->validate($rules);

        $data = [
            'username' => $this->username,
            'name' => $this->name,
            'email' => $this->email,
        ];

        if ($this->password) {
            $data['password'] = bcrypt($this->password);
        }

        if ($this->editingId) {
            $user = User::find($this->editingId);
            $user->update($data);
            $user->syncRoles($this->selectedRoles);
            $this->dispatch('notify', type: 'success', message: 'User berhasil diperbarui');
        } else {
            $user = User::create($data);
            $user->syncRoles($this->selectedRoles);
            $this->dispatch('notify', type: 'success', message: 'User berhasil ditambahkan');
        }

        $this->showModal = false;
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Tidak dapat menghapus akun sendiri');
            return;
        }
        User::findOrFail($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'User berhasil dihapus');
    }

    public function render()
    {
        $users = User::with('roles')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('username', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        $roles = Role::all();

        return view('livewire.admin.users', compact('users', 'roles'))
            ->title('Pengguna');
    }
}
