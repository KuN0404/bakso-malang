<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Supplier</h1>
            <p class="text-gray-500">Kelola daftar toko / supplier bahan baku</p>
        </div>
        @can('create_suppliers')
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Supplier
        </button>
        @endcan
    </div>

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau telepon supplier..."
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
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Telepon</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Alamat</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($suppliers as $supplier)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-800">{{ $supplier->name }}</p>
                            @if($supplier->note)
                                <p class="text-sm text-gray-500 truncate max-w-xs">{{ $supplier->note }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $supplier->phone ?: '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 truncate max-w-xs">{{ $supplier->address ?: '-' }}</td>
                        <td class="px-6 py-4">
                            @if($supplier->is_active)
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
                            @can('edit_suppliers')
                            <button wire:click="edit({{ $supplier->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            @endcan
                            @can('delete_suppliers')
                            <button
                                @click="$dispatch('confirm-action', {
                                    title: 'Hapus Supplier',
                                    message: 'Apakah Anda yakin ingin menghapus supplier {{ $supplier->name }}?',
                                    confirmText: 'Ya, Hapus',
                                    type: 'danger',
                                    action: { componentId: $wire.__instance.id, method: 'delete' },
                                    params: {{ $supplier->id }}
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
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="truck" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada supplier</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($suppliers->hasPages())
            <div class="px-6 py-4 border-t">{{ $suppliers->links() }}</div>
        @endif
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center" x-data x-on:keydown.escape.window="$wire.set('showModal', false)">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Supplier' : 'Tambah Supplier' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Supplier / Toko *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Contoh: Pasar Gede / Toko Daging">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">No. Telepon</label>
                        <input type="text" wire:model="phone" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="08123456789">
                        @error('phone') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                        <textarea wire:model="address" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                        @error('address') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea wire:model="note" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Opsional"></textarea>
                        @error('note') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
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
