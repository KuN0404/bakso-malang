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
                            <button 
                                @click="$dispatch('confirm-action', {
                                    title: 'Hapus Role',
                                    message: 'Apakah Anda yakin ingin menghapus role {{ $role->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    action: { componentId: $wire.__instance.id, method: 'delete' },
                                    params: {{ $role->id }}
                                })"
                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    @endif
                </div>
                <div class="p-5">
                    <p class="text-xs text-gray-500 mb-2">Permissions ({{ $role->permissions->count() }})</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse($role->permissions->take(8) as $perm)
                            <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">{{ config('permissions.labels.' . $perm->name, $perm->name) }}</span>
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
        <div 
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" 
            x-data="{ localPermissions: @js($selectedPermissions) }"
            @keydown.escape.window="$wire.set('showModal', false)"
        >
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] flex flex-col">
                <!-- Header - Fixed -->
                <div class="p-6 border-b flex justify-between items-center flex-none">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Role</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- Content - Scrollable -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Role *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500" placeholder="Manager, Kasir, dll">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Permissions</label>
                        @foreach($permissionGroups as $group => $perms)
                            <div class="mb-4">
                                <p class="text-xs font-semibold text-gray-500 uppercase mb-2">{{ $group }}</p>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($perms as $perm)
                                        <button 
                                            type="button"
                                            @click="localPermissions.includes('{{ $perm->name }}') ? localPermissions = localPermissions.filter(p => p !== '{{ $perm->name }}') : localPermissions.push('{{ $perm->name }}')"
                                            :class="localPermissions.includes('{{ $perm->name }}') ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 hover:bg-gray-50'"
                                            class="inline-flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition-all text-sm"
                                        >
                                            <span 
                                                :class="localPermissions.includes('{{ $perm->name }}') ? 'bg-primary-500' : 'bg-white border-2 border-gray-300'"
                                                class="w-4 h-4 rounded flex items-center justify-center transition-all"
                                            >
                                                <svg x-show="localPermissions.includes('{{ $perm->name }}')" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            {{ config('permissions.labels.' . $perm->name, $perm->name) }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                <!-- Footer - Fixed -->
                <div class="p-6 border-t bg-gray-50 flex gap-3 flex-none rounded-b-2xl">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">Batal</button>
                    <button 
                        type="button" 
                        @click="$wire.set('selectedPermissions', localPermissions); $wire.save()"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
                    >Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>
@script
<script>lucide.createIcons();Livewire.hook('morph.updated',()=>lucide.createIcons());</script>
@endscript
