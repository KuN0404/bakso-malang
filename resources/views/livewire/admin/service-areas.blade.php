<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Area Pelayanan</h1>
            <p class="text-gray-500">Kelola meja, ruangan, atau zona pelayanan</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Area
        </button>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="flex flex-wrap items-center gap-4">
            <div class="relative flex-1 min-w-[200px]">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>
            <select wire:model.live="filterType" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Tipe</option>
                @foreach($typeLabels as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto relative">
        <!-- Loading Overlay -->
        <div wire:loading.flex wire:target="search, filterType, gotoPage, previousPage, nextPage" class="absolute inset-0 bg-white/70 z-10 items-center justify-center">
            <div class="flex flex-col items-center gap-2">
                <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-gray-600 font-medium">Memuat data...</span>
            </div>
        </div>

        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Kapasitas</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($areas as $area)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-mono text-sm rounded">{{ $area->code }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $area->name }}</p>
                            @if($area->description)
                                <p class="text-sm text-gray-500 truncate max-w-xs">{{ $area->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $typeColors = [
                                    'table' => 'bg-blue-100 text-blue-700',
                                    'room' => 'bg-purple-100 text-purple-700',
                                    'zone' => 'bg-amber-100 text-amber-700',
                                    'other' => 'bg-gray-100 text-gray-700',
                                ];
                            @endphp
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium {{ $typeColors[$area->type] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $typeLabels[$area->type] ?? $area->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-600">
                            <span class="inline-flex items-center gap-1">
                                <i data-lucide="users" class="w-4 h-4"></i>
                                {{ $area->capacity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($area->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $area->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $area->id }})" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="map-pin" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada area pelayanan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($areas->hasPages())
            <div class="px-6 py-4 border-t">{{ $areas->links() }}</div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.closeModal()">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Area' : 'Tambah Area' }}</h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kode *</label>
                            <input type="text" wire:model="code" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 uppercase" placeholder="M1, VIP1">
                            @error('code') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                            <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Meja 1">
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <input type="text" wire:model="description" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Deskripsi opsional">
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipe *</label>
                            <select wire:model="type" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                                @foreach($typeLabels as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas</label>
                            <input type="number" wire:model="capacity" min="1" max="100" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                            @error('capacity') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                            @error('sort_order') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="closeModal" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
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

    <!-- Delete Confirmation Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl p-6">
                <div class="text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="alert-triangle" class="w-8 h-8 text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Hapus Area?</h3>
                    <p class="text-gray-500 mb-6">Area yang sudah memiliki transaksi tidak dapat dihapus.</p>
                    <div class="flex gap-3">
                        <button wire:click="cancelDelete" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button
                            wire:click="delete"
                            wire:loading.attr="disabled"
                            class="flex-1 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                        >Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    lucide.createIcons();
    Livewire.hook('morph.updated', () => {
        setTimeout(() => lucide.createIcons(), 50);
    });
    Livewire.hook('commit', ({ succeed }) => {
        succeed(() => {
            setTimeout(() => lucide.createIcons(), 100);
        });
    });
</script>
@endscript
