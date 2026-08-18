<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Satuan</h1>
            <p class="text-gray-500">Kelola daftar satuan untuk Bahan Baku &amp; Komponen</p>
        </div>
        @can('create_units')
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Satuan
        </button>
        @endcan
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau simbol satuan..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Simbol</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Grup</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Urutan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($units as $unit)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $unit->name }}</td>
                        <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $unit->symbol }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                {{ $unit->group }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $unit->sort_order }}</td>
                        <td class="px-6 py-4">
                            @if($unit->is_active)
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
                            @can('edit_units')
                            <button wire:click="edit({{ $unit->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            @endcan
                            @can('delete_units')
                            <button
                                @click="$dispatch('confirm-action', {
                                    title: 'Hapus Satuan',
                                    message: 'Apakah Anda yakin ingin menghapus satuan {{ $unit->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    action: { componentId: $wire.__instance.id, method: 'delete' },
                                    params: {{ $unit->id }}
                                })"
                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="ruler" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada satuan</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($units->hasPages())
            <div class="px-6 py-4 border-t">{{ $units->links() }}</div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Satuan' : 'Tambah Satuan' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nama Satuan *</label>
                            <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Contoh: Kilogram">
                            @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Simbol *</label>
                            <input type="text" wire:model="symbol" maxlength="10" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Contoh: kg">
                            @error('symbol') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grup *</label>
                        <select wire:model="group" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                            @foreach($groupOptions as $opt)
                                <option value="{{ $opt }}">{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('group') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Urutan</label>
                        <input type="number" wire:model="sort_order" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500 @error('sort_order') border-red-500 @enderror">
                        @error('sort_order')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @else
                            <p class="text-gray-500 text-xs mt-1">Urutan terakhir: {{ $this->lastSortOrder }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model="is_active" id="is_active" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                        <label for="is_active" class="text-sm text-gray-700">Aktif</label>
                    </div>
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
