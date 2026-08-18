<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Role;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
class Users extends Component
{
    use WithPagination, WithFileUploads;

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

    public string $statusFilter = 'active';

    public bool $isEditingSuperAdmin = false;

    // Profil (opsional)
    public ?string $phone = null;
    public ?string $birth_date = null;
    public ?string $address = null;
    public ?string $gender = null;
    public ?string $notes = null;
    public $photo = null;
    public ?string $existingPhoto = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'username', 'name', 'email', 'password', 'selectedRoles', 'isEditingSuperAdmin',
            'phone', 'birth_date', 'address', 'gender', 'notes', 'photo', 'existingPhoto',
        ]);
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::getForEdit($id);

        if (!$user->isActive()) {
            $this->dispatch('notify', type: 'error', message: 'Aktifkan user ini terlebih dahulu sebelum mengubah datanya.');
            return;
        }

        // Proteksi: Akun Super Admin HANYA BISA diubah oleh sesama Super Admin
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'Hanya Super Admin yang berhak mengubah akun Super Admin.');
            return;
        }

        $this->editingId = $user->id;
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->selectedRoles = $user->roles->pluck('name')->toArray();
        $this->isEditingSuperAdmin = $user->isSuperAdmin();

        // Profil sudah eager-loaded lewat User::getForEdit() (with 'profile') — tidak ada query tambahan di sini.
        $profile = $user->profile;
        $this->phone = $profile?->phone;
        $this->birth_date = $profile?->birth_date?->format('Y-m-d');
        $this->address = $profile?->address;
        $this->gender = $profile?->gender;
        $this->notes = $profile?->notes;
        $this->existingPhoto = $profile?->photo;
        $this->photo = null;

        $this->showModal = true;
    }

    protected function deleteOldPhoto(?string $photoPath): void
    {
        if ($photoPath && Storage::disk('public')->exists($photoPath)) {
            Storage::disk('public')->delete($photoPath);
        }
    }

    /**
     * Process photo: Resize, Convert to WebP, and Save
     */
    protected function processPhoto($photo): ?string
    {
        if (!$photo) return null;

        $filename = md5($photo->getClientOriginalName() . time()) . '.webp';
        $path = 'staff/' . $filename;

        $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $img = $manager->read($photo->getRealPath());

        $img->scaleDown(width: 800);

        Storage::disk('public')->put($path, (string) $img->toWebp(quality: 80));

        return $path;
    }

    public function removePhoto(): void
    {
        if ($this->editingId && $this->existingPhoto) {
            $user = User::getForEdit($this->editingId);
            $this->deleteOldPhoto($this->existingPhoto);
            $user->profile()->update(['photo' => null]);
        }

        $this->existingPhoto = null;
        $this->photo = null;
    }

    protected function profileValidationRules(): array
    {
        return [
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
            'gender' => 'nullable|in:L,P',
            'notes' => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
        ];
    }

    protected function saveProfile(User $user): void
    {
        $profileData = [
            'phone' => $this->phone ?: null,
            'birth_date' => $this->birth_date ?: null,
            'address' => $this->address ?: null,
            'gender' => $this->gender ?: null,
            'notes' => $this->notes ?: null,
        ];

        if ($this->photo) {
            $newPhotoPath = $this->processPhoto($this->photo);
            if ($newPhotoPath) {
                if ($this->existingPhoto) {
                    $this->deleteOldPhoto($this->existingPhoto);
                }
                $profileData['photo'] = $newPhotoPath;
            }
        }

        $user->profile()->updateOrCreate([], $profileData);
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'edit_users' : 'create_users');

        // Proteksi simpan: Akun Super Admin HANYA BISA diubah oleh sesama Super Admin
        if ($this->editingId) {
            $targetUser = User::find($this->editingId);
            if ($targetUser && $targetUser->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
                $this->dispatch('notify', type: 'error', message: 'Hanya Super Admin yang berhak mengubah akun Super Admin.');
                $this->showModal = false;
                return;
            }
        }

        // Logic khusus Super Admin
        if ($this->isEditingSuperAdmin) {
            $exceptId = $this->editingId ?: 'NULL';
            $this->validate(array_merge([
                'name'  => 'required|min:2|max:100',
                'email' => "required|email|unique:users,email,{$exceptId},id,deleted_at,NULL",
                'password' => 'nullable|min:6',
            ], $this->profileValidationRules()));

            $user = User::find($this->editingId);
            $data = [
                'name'  => $this->name,
                'email' => $this->email,
            ];

            if ($this->password) {
                $data['password'] = bcrypt($this->password);
            }

            $user->update($data);

            // Perkuat: Pastikan role Super Admin tidak dapat dilepas
            if (!$user->hasRole('Super Admin')) {
                $user->assignRole('Super Admin');
            }

            $this->saveProfile($user);

            $this->dispatch('notify', type: 'success', message: 'Data Super Admin berhasil diperbarui');
            $this->showModal = false;
            return;
        }

        // Logic User Biasa
        if (in_array('Super Admin', $this->selectedRoles) && !$this->isEditingSuperAdmin) {
            $this->dispatch('notify', type: 'error', message: 'Sistem hanya memperbolehkan 1 akun Super Admin utama.');
            return;
        }

        $exceptId = $this->editingId ?: 'NULL';
        $rules = array_merge([
            'username' => "required|min:3|max:50|unique:users,username,{$exceptId},id,deleted_at,NULL",
            'name' => 'required|min:2|max:100',
            'email' => "required|email|unique:users,email,{$exceptId},id,deleted_at,NULL",
            'selectedRoles' => 'required|array|min:1',
        ], $this->profileValidationRules());

        if (!$this->editingId) {
            $rules['password'] = 'required|min:6';
        }

        $this->validate($rules, [
            'selectedRoles.required' => 'Wajib memilih minimal satu role.',
            'selectedRoles.min' => 'Wajib memilih minimal satu role.',
        ]);

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
            $this->saveProfile($user);
            $this->dispatch('notify', type: 'success', message: 'User berhasil diperbarui');
        } else {
            $user = User::create($data);
            $user->syncRoles($this->selectedRoles);
            $this->saveProfile($user);
            $this->dispatch('notify', type: 'success', message: 'User berhasil ditambahkan');
        }

        $this->showModal = false;
    }

    public function deactivate(int $id): void
    {
        $this->authorize('delete_users');

        // 1. Tidak dapat menonaktifkan akun sendiri
        if ($id === auth()->id()) {
            $this->dispatch('notify', type: 'error', message: 'Anda tidak dapat menonaktifkan akun Anda sendiri');
            return;
        }

        $user = User::getForEdit($id);

        // 2. Super Admin TIDAK BISA DINONAKTIFKAN oleh SIAPAPUN (termasuk sesama Super Admin)
        if ($user->isSuperAdmin()) {
            $this->dispatch('notify', type: 'error', message: 'User Super Admin tidak dapat dinonaktifkan oleh siapapun');
            return;
        }

        if (!$user->isActive()) {
            $this->dispatch('notify', type: 'error', message: 'User sudah dalam keadaan nonaktif');
            return;
        }

        $user->deactivate();
        $this->dispatch('notify', type: 'success', message: 'User berhasil dinonaktifkan dan dikeluarkan dari semua sesi aktif');
    }

    public function activate(int $id): void
    {
        $this->authorize('activate_users');

        $user = User::getForEdit($id);

        if ($user->isActive()) {
            $this->dispatch('notify', type: 'error', message: 'User sudah dalam keadaan aktif');
            return;
        }

        if ($user->hasActiveUsernameOrEmailConflict()) {
            $this->dispatch('notify', type: 'error', message: 'Tidak dapat mengaktifkan: username atau email sudah dipakai user aktif lain. Ubah username/email user tersebut terlebih dahulu.');
            return;
        }

        $user->activate();
        $this->dispatch('notify', type: 'success', message: 'User berhasil diaktifkan kembali');
    }

    public function render()
    {
        $users = User::getPaginated($this->search, $this->statusFilter, 10);
        $roles = Role::getAssignableRoles();
        // Dihitung sekali di sini (bukan auth()->user()->hasRole(...) di dalam loop blade)
        // supaya tidak memicu query 'roles' terpisah untuk instance auth()->user(),
        // yang beda objek dari baris user yang sudah di-eager-load di atas.
        $isSuperAdmin = auth()->user()->hasRole('Super Admin');

        return view('livewire.admin.users', compact('users', 'roles', 'isSuperAdmin'))
            ->title('Pengguna');
    }
}
