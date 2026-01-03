<div x-data="{ 
    showDeleteModal: false, 
    deleteId: null, 
    deleteName: '',
    openDelete(id, name) {
        this.deleteId = id;
        this.deleteName = name;
        this.showDeleteModal = true;
    },
    confirmDelete() {
        $wire.delete(this.deleteId);
        this.showDeleteModal = false;
    }
}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Metode Pembayaran</h1>
            <p class="text-gray-500">Kelola metode pembayaran yang tersedia</p>
        </div>
        <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            Tambah Metode
        </button>
    </div>

    <!-- Search & Count -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-4 p-4">
        <div class="flex items-center justify-between gap-4">
            <div class="relative flex-1 max-w-md">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari metode pembayaran..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                >
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>
            <span class="text-sm text-gray-500">{{ $sources->total() }} metode</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tipe</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Urutan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($sources as $source)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                    <i data-lucide="wallet" class="w-5 h-5 text-green-600"></i>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-800">{{ $source->name }}</p>
                                    @if($source->description)
                                        <p class="text-sm text-gray-500">{{ $source->description }}</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">{{ ucfirst($source->type) }}</span>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $source->sort_order }}</td>
                        <td class="px-6 py-4">
                            @if($source->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="edit({{ $source->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="edit" class="w-4 h-4"></i>
                            </button>
                            <button 
                                @click="openDelete({{ $source->id }}, '{{ addslashes($source->name) }}')"
                                class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                            >
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="wallet" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada metode pembayaran</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        
        @if($sources->hasPages())
            <div class="p-4 border-t bg-gray-50">
                {{ $sources->links(data: ['scrollTo' => false]) }}
            </div>
        @endif
    </div>

    @if($showModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit' : 'Tambah' }} Metode</h3>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600"><i data-lucide="x" class="w-6 h-6"></i></button>
                </div>
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Cash, QRIS, dll">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tipe *</label>
                        <select wire:model="type" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            <option value="cash">Cash</option>
                            <option value="card">Kartu (Debit/Credit)</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="ewallet">E-Wallet</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <input type="text" wire:model="description" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Opsional">
                        @error('description') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Urutan *</label>
                            <input type="number" wire:model="sort_order" min="0" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                            @error('sort_order') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-end">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <span class="text-sm text-gray-700">Aktif</span>
                            </label>
                        </div>
                    </div>
                    <div class="flex gap-3 pt-4">
                        <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg">Batal</button>
                        <button type="submit" class="flex-1 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal (Glassmorphism) -->
    <div 
        x-show="showDeleteModal" 
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div 
            x-show="showDeleteModal"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            @click.away="showDeleteModal = false"
            class="bg-white/80 backdrop-blur-md rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 border border-white/50 text-center"
        >
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="trash-2" class="w-8 h-8 text-red-600"></i>
            </div>
            
            <h3 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Hapus</h3>
            <p class="text-gray-600 mb-6">
                Hapus metode pembayaran "<span x-text="deleteName" class="font-semibold"></span>"?
            </p>
            
            <div class="flex gap-3">
                <button 
                    @click="showDeleteModal = false" 
                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
                >
                    Batal
                </button>
                
                <button 
                    @click="confirmDelete()" 
                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl shadow-lg shadow-red-600/30 transition-colors"
                >
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</div>
@script
<script>
lucide.createIcons();
Livewire.hook('morph.updated', () => queueMicrotask(() => lucide.createIcons()));
</script>
@endscript
