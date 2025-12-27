<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Role & Permission</h1>
            <p class="text-gray-500">Kelola role dan hak akses</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Role
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($roles as $role)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-5 border-b flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                            <i data-lucide="shield" class="w-5 h-5 text-primary-600"></i>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $role->name }}</h3>
                            <p class="text-xs text-gray-500">{{ $role->users_count }} user</p>
                        </div>
                    </div>
                    @if($role->name !== 'Super Admin')
                        <div class="flex gap-1">
                            <button wire:click="edit({{ $role->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="delete({{ $role->id }})" wire:confirm="Hapus role ini?" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-2">Permissions ({{ $role->permissions->count() }})</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse($role->permissions->take(8) as $perm)
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ $perm->name }}</span>
                        @empty
                            <span class="text-gray-400 text-xs">Tidak ada permission</span>
                        @endforelse
                        @if($role->permissions->count() > 8)
                            <span class="px-2 py-0.5 bg-primary-100 text-primary-600 text-xs rounded">+{{ $role->permissions->count() - 8 }} lainnya</span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Role</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Role *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Manager, Kasir, dll">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                        @foreach($permissionGroups as $group => $perms)
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ ucfirst($group) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($perms as $perm)
                                        <label class="inline-flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer hover:bg-gray-50 {{ in_array($perm->name, $selectedPermissions) ? 'border-primary-500 bg-primary-50' : 'border-gray-200' }}">
                                            <input type="checkbox" wire:model="selectedPermissions" value="{{ $perm->name }}" class="sr-only">
                                            <span class="text-sm">{{ $perm->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
