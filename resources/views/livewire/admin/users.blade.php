<div x-init="lucide.createIcons()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Pengguna</h1>
            <p class="text-gray-500">Kelola pengguna sistem</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah User
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4 space-y-3">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari user..." class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
        <div class="flex items-center gap-2">
            <button
                wire:click="$set('statusFilter', 'active')"
                class="px-4 py-1.5 rounded-lg font-semibold text-xs whitespace-nowrap transition-all {{ $statusFilter === 'active' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
            >Aktif</button>
            <button
                wire:click="$set('statusFilter', 'inactive')"
                class="px-4 py-1.5 rounded-lg font-semibold text-xs whitespace-nowrap transition-all {{ $statusFilter === 'inactive' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
            >Nonaktif</button>
            <button
                wire:click="$set('statusFilter', 'all')"
                class="px-4 py-1.5 rounded-lg font-semibold text-xs whitespace-nowrap transition-all {{ $statusFilter === 'all' ? 'bg-primary-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}"
            >Semua</button>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">User</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($users as $user)
                    <tr class="hover:bg-gray-50 {{ !$user->isActive() ? 'opacity-60' : '' }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 {{ $user->hasRole('Super Admin') ? 'bg-purple-100' : 'bg-primary-100' }} rounded-full flex items-center justify-center">
                                    <i data-lucide="{{ $user->hasRole('Super Admin') ? 'shield-check' : 'user' }}" class="w-5 h-5 {{ $user->hasRole('Super Admin') ? 'text-purple-600' : 'text-primary-600' }}"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $user->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $user->username }}</td>
                        <td class="px-6 py-4">
                            @foreach($user->roles as $role)
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-6 py-4">
                            @if($user->isActive())
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium bg-gray-200 text-gray-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if(!$user->isActive())
                                <button disabled class="p-2 text-gray-300 rounded-lg cursor-not-allowed" title="Aktifkan user ini terlebih dahulu untuk mengubah datanya">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            @elseif(!$user->hasRole('Super Admin') || $isSuperAdmin)
                                <button wire:click="edit({{ $user->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100" title="Edit User">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            @else
                                <button disabled class="p-2 text-gray-300 rounded-lg cursor-not-allowed" title="Hanya Super Admin yang berhak mengedit Super Admin">
                                    <i data-lucide="edit" class="w-4 h-4"></i>
                                </button>
                            @endif

                            @if($user->hasRole('Super Admin'))
                                <button disabled class="p-2 text-gray-300 rounded-lg cursor-not-allowed" title="User Super Admin tidak dapat dinonaktifkan">
                                    <i data-lucide="ban" class="w-4 h-4"></i>
                                </button>
                            @elseif(!$user->isActive())
                                <button
                                    @click="$dispatch('confirm-action', {
                                        title: 'Aktifkan User',
                                        message: 'Aktifkan kembali user {{ $user->name }}?',
                                        confirmText: 'Ya, Aktifkan',
                                        type: 'info',
                                        action: { componentId: $wire.__instance.id, method: 'activate' },
                                        params: {{ $user->id }}
                                    })"
                                    class="p-2 text-gray-400 hover:text-green-600 rounded-lg hover:bg-gray-100"
                                    title="Aktifkan User"
                                >
                                    <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                </button>
                            @elseif($user->id !== auth()->id())
                                <button
                                    @click="$dispatch('confirm-action', {
                                        title: 'Nonaktifkan User',
                                        message: 'Apakah Anda yakin ingin menonaktifkan user {{ $user->name }}? User akan langsung dikeluarkan dari sesi yang sedang aktif dan dapat diaktifkan kembali kapan saja.',
                                        confirmText: 'Ya, Nonaktifkan',
                                        type: 'danger',
                                        action: { componentId: $wire.__instance.id, method: 'deactivate' },
                                        params: {{ $user->id }}
                                    })"
                                    class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                    title="Nonaktifkan User"
                                >
                                    <i data-lucide="ban" class="w-4 h-4"></i>
                                </button>
                            @else
                                <button disabled class="p-2 text-gray-300 rounded-lg cursor-not-allowed" title="Tidak dapat menonaktifkan akun sendiri">
                                    <i data-lucide="ban" class="w-4 h-4"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">Belum ada user</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($users->hasPages())
            <div class="px-6 py-4 border-t">{{ $users->links() }}</div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" wire:click.self="$set('showModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit User' : 'Tambah User' }}</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                        <input type="text" wire:model="username" 
                            class="w-full px-4 py-2 border border-gray-200 rounded-lg {{ $isEditingSuperAdmin ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' }}"
                            {{ $isEditingSuperAdmin ? 'disabled' : '' }}>
                        @error('username') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" wire:model="email" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        @error('email') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password {{ $editingId ? '(kosongkan jika tidak diubah)' : '*' }}</label>
                        <input type="password" wire:model="password" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                        @error('password') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    
                    @if(!$isEditingSuperAdmin)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Roles *</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($roles as $role)
                                <label class="inline-flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 {{ in_array($role->name, $selectedRoles) ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                    <input type="checkbox" wire:model.live="selectedRoles" value="{{ $role->name }}" class="sr-only">
                                    <span class="text-sm font-medium {{ in_array($role->name, $selectedRoles) ? 'text-primary-700' : 'text-gray-700' }}">{{ $role->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('selectedRoles') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    @endif

                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
