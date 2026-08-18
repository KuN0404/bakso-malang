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
                                @if($user->profile?->photo)
                                    <img src="{{ asset('storage/' . $user->profile->photo) }}" class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div class="w-10 h-10 {{ $user->hasRole('Super Admin') ? 'bg-purple-100' : 'bg-primary-100' }} rounded-full flex items-center justify-center flex-shrink-0">
                                        <i data-lucide="{{ $user->hasRole('Super Admin') ? 'shield-check' : 'user' }}" class="w-5 h-5 {{ $user->hasRole('Super Admin') ? 'text-purple-600' : 'text-primary-600' }}"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800">
                                        {{ $user->name }}
                                        @if($user->profile?->age !== null)
                                            <span class="text-xs font-normal text-gray-400">({{ $user->profile->age }} tahun)</span>
                                        @endif
                                    </p>
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
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" wire:click.self="$set('showModal', false)" x-data="{ activeTab: 'akun' }">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="p-6 border-b flex justify-between items-center flex-none">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit User' : 'Tambah User' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>

                <!-- Tabs -->
                <div class="flex gap-1 px-6 pt-4 border-b flex-none">
                    <button type="button" @click="activeTab = 'akun'"
                        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                        :class="activeTab === 'akun' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        Akun
                    </button>
                    <button type="button" @click="activeTab = 'profil'"
                        class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition-colors"
                        :class="activeTab === 'profil' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                        Profil
                    </button>
                </div>

                <form wire:submit="save" class="flex flex-col flex-1 min-h-0">
                    <!-- Content (scrollable) -->
                    <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                        <!-- Tab: Akun -->
                        <div x-show="activeTab === 'akun'" x-cloak class="space-y-4">
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
                        </div>

                        <!-- Tab: Profil -->
                        <div x-show="activeTab === 'profil'" x-cloak class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">No HP</label>
                                    <input type="text" wire:model="phone" placeholder="08123456789" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                    @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Lahir</label>
                                    <div
                                        wire:ignore
                                        wire:key="birth-date-picker-{{ $editingId ?? 'new' }}"
                                        x-data="{
                                            init() {
                                                flatpickr(this.$refs.dateInput, {
                                                    locale: 'id',
                                                    dateFormat: 'Y-m-d',
                                                    altInput: true,
                                                    altFormat: 'j F Y',
                                                    defaultDate: @js($birth_date),
                                                    maxDate: 'today',
                                                    animate: true,
                                                    onChange: (selectedDates, dateStr) => {
                                                        // false = jangan kirim request Livewire terpisah di sini
                                                        // (nilai ikut terkirim saat submit form).
                                                        $wire.set('birth_date', dateStr, false);
                                                    }
                                                });
                                            }
                                        }"
                                        x-init="init()"
                                    >
                                        <input x-ref="dateInput" type="text" placeholder="Pilih tanggal" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                    </div>
                                    @error('birth_date') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Kelamin</label>
                                <select wire:model="gender" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                    <option value="">- Pilih -</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                @error('gender') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                                <textarea wire:model="address" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg"></textarea>
                                @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan</label>
                                <textarea wire:model="notes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg"></textarea>
                                @error('notes') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Foto</label>
                                <input type="file" wire:model="photo" accept="image/png,image/jpeg,image/jpg" class="w-full px-4 py-2 border rounded-lg @error('photo') border-red-500 @else border-gray-200 @enderror">
                                @error('photo')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @else
                                    <p class="text-gray-500 text-xs mt-1">Format: PNG, JPG, JPEG. Maks 2MB. Otomatis dikonversi ke WebP.</p>
                                @enderror

                                @if($photo)
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ $photo->temporaryUrl() }}" class="w-16 h-16 object-cover rounded-full border border-gray-200">
                                        <span class="text-sm text-green-600">Foto baru siap diupload</span>
                                    </div>
                                @elseif($existingPhoto)
                                    <div class="mt-2 flex items-center gap-3">
                                        <img src="{{ asset('storage/' . $existingPhoto) }}" class="w-16 h-16 object-cover rounded-full border border-gray-200">
                                        <button
                                            type="button"
                                            wire:click="removePhoto"
                                            wire:confirm="Hapus foto ini?"
                                            class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            Hapus foto
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="p-6 border-t bg-gray-50 flex gap-3 flex-none rounded-b-2xl">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2 bg-white border border-gray-200 hover:bg-gray-100 text-gray-700 font-medium rounded-lg">Batal</button>
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
