<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="create, edit, save" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-blue-600">Memuat...</span>
        </div>
    </div>

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Komponen / Item Setengah Jadi</h1>
            <p class="text-sm text-gray-500 mt-0.5">Master komponen / item setengah jadi. Penambahan stok komponen HANYA berasal dari <strong>Repacking / Produksi</strong>.</p>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0 whitespace-nowrap self-start sm:self-auto">
            @can('create_productions')
                <a href="{{ route('admin.productions.index') }}" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors text-sm">
                    <i data-lucide="package-plus" class="w-4 h-4"></i>
                    <span>Repacking / Produksi</span>
                </a>
            @endcan
            @can('create_components')
                <button wire:click="create" class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors text-sm">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    <span>Tambah Komponen</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- Stock Alert Banners -->
    @if($outOfStockCount > 0)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-sm">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 flex-shrink-0"></i>
            <span class="text-red-800 font-medium">{{ $outOfStockCount }} komponen <strong>habis stok</strong>. Lakukan repacking segera.</span>
        </div>
    @endif
    @if($lowStockCount > 0)
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-xl flex items-center gap-3 text-sm">
            <i data-lucide="triangle-alert" class="w-5 h-5 text-yellow-600 flex-shrink-0"></i>
            <span class="text-yellow-800 font-medium">{{ $lowStockCount }} komponen <strong>stok tipis</strong> (di bawah batas minimum).</span>
        </div>
    @endif

    <!-- Search -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama atau kode komponen..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Nama Komponen</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Satuan</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Stok</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Min Stok</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">HPP / Unit</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($components as $comp)
                    @php $status = $comp->getStockStatus(); @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $status === 'out' ? 'bg-red-50/40' : ($status === 'low' ? 'bg-yellow-50/40' : '') }}">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-blue-700">{{ $comp->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $comp->name }}</td>
                        <td class="px-6 py-4 text-gray-500 text-sm">{{ $comp->unit?->symbol }}</td>
                        <td class="px-6 py-4 text-right font-bold {{ $status === 'out' ? 'text-red-600' : ($status === 'low' ? 'text-yellow-700' : 'text-gray-900') }}">
                            {{ number_format($comp->stock, 2, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-gray-500">{{ number_format($comp->minimum_stock, 2, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right text-sm text-gray-700">Rp {{ number_format($comp->cost_price, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($status === 'out')
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">
                                    <i data-lucide="x-circle" class="w-3 h-3"></i> Habis
                                </span>
                            @elseif($status === 'low')
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-yellow-100 text-yellow-700 text-xs font-semibold rounded-full">
                                    <i data-lucide="triangle-alert" class="w-3 h-3"></i> Tipis
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">
                                    <i data-lucide="check-circle" class="w-3 h-3"></i> OK
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                @can('edit_components')
                                    <button wire:click="edit({{ $comp->id }})" title="Edit Komponen"
                                        class="p-2 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50">
                                        <i data-lucide="pencil" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                                @can('delete_components')
                                    <button wire:click="delete({{ $comp->id }})" wire:confirm="Hapus komponen '{{ $comp->name }}'?" title="Hapus"
                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="layers" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p class="font-medium">Belum ada komponen</p>
                            <p class="text-sm mt-1">Tambahkan komponen seperti Bakso Kecil, Bakso Besar, Kuah, dll.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($components->total() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <span class="text-sm text-gray-500">Total: {{ $components->total() }} komponen</span>
                <div>{{ $components->links() }}</div>
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-bold text-gray-800">{{ $editingId ? 'Edit Komponen' : 'Tambah Komponen Baru' }}</h3>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <form wire:submit.prevent="save" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kode <span class="text-red-500">*</span></label>
                        <input wire:model="code" type="text" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 font-mono" placeholder="CMP-001">
                        @error('code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Satuan <span class="text-red-500">*</span></label>
                        <select wire:model="unit_id" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
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
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Komponen <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="w-full text-sm border border-gray-200 rounded-lg p-2.5" placeholder="cth: Bakso Kecil, Kuah Kaldu...">
                    @error('name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Batas Stok Minimum</label>
                        <input wire:model="minimumStock" type="number" step="0.01" min="0" class="w-full text-sm border border-gray-200 rounded-lg p-2.5" placeholder="0">
                        <p class="text-xs text-gray-400 mt-1">Warning muncul jika stok ≤ nilai ini</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">HPP Awal / Unit (Rp)</label>
                        <input wire:model="costPrice" type="number" step="1" min="0" class="w-full text-sm border border-gray-200 rounded-lg p-2.5" placeholder="0">
                        <p class="text-xs text-gray-400 mt-1">Akan diupdate otomatis saat repacking</p>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan</label>
                    <textarea wire:model="note" rows="2" class="w-full text-sm border border-gray-200 rounded-lg p-2.5" placeholder="Keterangan komponen..."></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input wire:model="isActive" type="checkbox" id="comp-active" class="rounded border-gray-300 text-blue-600">
                    <label for="comp-active" class="text-sm text-gray-700">Komponen Aktif</label>
                </div>

                <div class="flex gap-3 pt-2 border-t border-gray-100">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" class="flex-1 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm">
                        {{ $editingId ? 'Simpan Perubahan' : 'Tambah Komponen' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>

@script
<script>
    lucide.createIcons();
</script>
@endscript
