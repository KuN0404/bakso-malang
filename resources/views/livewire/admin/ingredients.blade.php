<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="edit, openStockModal, openHistoryModal, create, save, saveStock" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memuat...</span>
        </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Bahan Baku</h1>
            <p class="text-gray-500">Kelola daftar bahan mentah & bumbu persediaan dapur</p>
        </div>
        @can('create_ingredients')
            <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Tambah Bahan Baku
            </button>
        @endcan
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode bahan baku..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama Bahan Baku</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stok Saat Ini</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stok Min.</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Est. HPP / Satuan</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($ingredients as $ing)
                    @php $stockStatus = $ing->getStockStatus(); @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-gray-500">{{ $ing->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <div>{{ $ing->name }}</div>
                            @if($ing->note)
                                <span class="text-xs text-gray-400 font-normal">{{ $ing->note }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold">
                            <span class="{{ $stockStatus === 'out' ? 'text-red-600 bg-red-50 px-2 py-0.5 rounded' : ($stockStatus === 'low' ? 'text-yellow-700 bg-yellow-50 px-2 py-0.5 rounded' : 'text-emerald-600') }}">
                                {{ number_format($ing->stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}
                            </span>
                            @if($stockStatus === 'out')
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">Habis</span>
                            @elseif($stockStatus === 'low')
                                <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-700">Menipis</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ number_format($ing->minimum_stock, 2, ',', '.') }} {{ $ing->unit?->symbol }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">Rp {{ number_format($ing->cost_price, 0, ',', '.') }} / {{ $ing->unit?->symbol }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($ing->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> Aktif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                    <i data-lucide="x" class="w-3 h-3"></i> Nonaktif
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button wire:click="openStockModal({{ $ing->id }})" title="Koreksi Stok" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100">
                                    <i data-lucide="box" class="w-4 h-4"></i>
                                </button>
                                <button wire:click="openHistoryModal({{ $ing->id }})" title="Riwayat Stok" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100">
                                    <i data-lucide="history" class="w-4 h-4"></i>
                                </button>
                                @can('edit_ingredients')
                                    <button wire:click="edit({{ $ing->id }})" title="Edit" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                                @can('delete_ingredients')
                                    <button 
                                        @click="$dispatch('confirm-action', {
                                            title: 'Hapus Bahan Baku',
                                            message: 'Apakah Anda yakin ingin menghapus bahan baku {{ $ing->name }}?',
                                            confirmText: 'Ya, Hapus',
                                            type: 'danger',
                                            action: { componentId: $wire.__instance.id, method: 'delete' },
                                            params: {{ $ing->id }}
                                        })"
                                        title="Hapus" 
                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                    >
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="leaf" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada data bahan baku</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($ingredients->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" class="border-gray-200 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 px-3">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>bahan baku</span>
                </div>
                <div>
                    {{ $ingredients->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800">{{ $editingId ? 'Edit Bahan Baku' : 'Tambah Bahan Baku Baru' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form wire:submit.prevent="save" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Bahan</label>
                        <input wire:model="code" type="text" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 font-mono">
                        @error('code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Satuan</label>
                        <select wire:model="unit_id" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <option value="">-- Pilih Satuan --</option>
                            @foreach($unitsGrouped as $groupLabel => $unitsInGroup)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach($unitsInGroup as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('unit_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Bahan Baku</label>
                    <input wire:model="name" type="text" placeholder="Contoh: Daging Sapi Segar" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    @if(!$editingId)
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Stok Awal</label>
                        <input wire:model="stock" type="number" step="0.01" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    </div>
                    @endif
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Stok Minimum Peringatan</label>
                        <input wire:model="minimum_stock" type="number" step="0.01" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        @error('minimum_stock') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Estimasi HPP Per Satuan (Rp)</label>
                        <input wire:model="cost_price" type="number" step="100" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                        @error('cost_price') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan / Keterangan Opsional</label>
                    <textarea wire:model="note" rows="2" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input wire:model="is_active" type="checkbox" id="is_active_ing" class="rounded text-primary-600 focus:ring-primary-500">
                    <label for="is_active_ing" class="text-sm font-medium text-gray-700">Aktifkan Bahan Baku Ini</label>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="flex-1 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Stock Adjustment Modal -->
    @if($showStockModal && $selectedIngredient)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800">Koreksi Stok: {{ $selectedIngredient->name }}</h3>
                <button wire:click="$set('showStockModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form wire:submit.prevent="saveStock" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jenis Penyesuaian</label>
                    <select wire:model="stockAdjustmentType" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                        <option value="add">Tambah Stok</option>
                        <option value="sub">Kurangi Stok</option>
                        <option value="set">Set Ulang Total Stok</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Jumlah ({{ $selectedIngredient->unit?->symbol }})</label>
                    <input wire:model="stockAdjustmentAmount" type="number" step="0.01" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                    @error('stockAdjustmentAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Penyesuaian</label>
                    <input wire:model="stockNote" type="text" placeholder="Alasan pengurangan / penyesuaian..." class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                    @error('stockNote') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="$set('showStockModal', false)" class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="saveStock" class="flex-1 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="saveStock">Simpan Stok</span>
                        <span wire:loading wire:target="saveStock" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Stock History Modal -->
    @if($showHistoryModal && $historyIngredient)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800">Riwayat Stok: {{ $historyIngredient->name }}</h3>
                <button wire:click="$set('showHistoryModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b text-gray-600 font-semibold">
                                <th class="py-2 px-3">Waktu Log</th>
                                <th class="py-2 px-3">Aksi Mutasi</th>
                                <th class="py-2 px-3">Perubahan Qty</th>
                                <th class="py-2 px-3">Stok Akhir</th>
                                <th class="py-2 px-3">Catatan</th>
                                <th class="py-2 px-3">Petugas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($historyLogs as $log)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-2.5 px-3 text-xs text-gray-500">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 px-3">
                                        @if($log->type === 'purchase' || $log->type === 'initial')
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800">Pembelian / Awal</span>
                                        @elseif($log->type === 'production_use')
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-purple-100 text-purple-800">Dipakai Produksi</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Koreksi Stok</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 px-3 font-bold {{ $log->type === 'production_use' || $log->type === 'sub' ? 'text-red-600' : 'text-emerald-600' }}">
                                        {{ $log->type === 'production_use' || $log->type === 'sub' ? '-' : '+' }}{{ number_format($log->amount, 2, ',', '.') }} {{ $historyIngredient->unit?->symbol }}
                                    </td>
                                    <td class="py-2.5 px-3 font-semibold text-gray-800">{{ number_format($log->final_stock, 2, ',', '.') }} {{ $historyIngredient->unit?->symbol }}</td>
                                    <td class="py-2.5 px-3 text-xs text-gray-500">{{ $log->note ?: '-' }}</td>
                                    <td class="py-2.5 px-3 text-xs text-gray-500">{{ $log->user?->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 px-3 text-center text-gray-500">Belum ada riwayat mutasi stok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($historyLogs && $historyLogs->count() > 0)
                    <div>{{ $historyLogs->links() }}</div>
                @endif

                <div class="pt-4 flex justify-end border-t">
                    <button wire:click="$set('showHistoryModal', false)" class="px-5 py-2 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
