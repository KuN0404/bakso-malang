<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="create, save, viewDetail" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
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
            <h1 class="text-2xl font-bold text-gray-800">Repacking / Produksi</h1>
            <p class="text-gray-500">Konversi bahan mentah menjadi <strong>komponen setengah jadi</strong> (Bakso, Kuah, dll) & hitung HPP otomatis</p>
        </div>
        @can('create_productions')
            <button wire:click="create" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Catat Repacking
            </button>
        @endcan
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari kode batch produksi..."
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-emerald-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kode Batch</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Bahan Baku</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Komponen Dihasilkan</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Petugas</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productions as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-emerald-700">{{ $p->production_code }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $p->production_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-600">
                            @foreach($p->inputs->take(2) as $in)
                                <div>- {{ $in->ingredient?->name }}: {{ number_format($in->quantity, 2, ',', '.') }} {{ $in->ingredient?->unit?->symbol }}</div>
                            @endforeach
                            @if($p->inputs->count() > 2)
                                <span class="text-gray-400">+{{ $p->inputs->count() - 2 }} bahan lainnya</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-800 font-medium">
                            @foreach($p->outputs->take(2) as $out)
                                <div>+ {{ $out->getOutputName() }}: {{ number_format($out->quantity, 0) }} {{ $out->getOutputUnit() }}</div>
                            @endforeach
                            @if($p->outputs->count() > 2)
                                <span class="text-gray-400">+{{ $p->outputs->count() - 2 }} komponen lainnya</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($p->total_cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $p->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewDetail({{ $p->id }})" title="Detail Produksi" class="p-2 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="package-open" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada riwayat repacking / produksi dapur</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($productions->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" class="border-gray-200 rounded-lg text-sm focus:ring-emerald-500 focus:border-emerald-500 py-1.5 px-3">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>produksi</span>
                </div>
                <div>
                    {{ $productions->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create Production Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden border border-gray-100 max-h-[92vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800">Form Repacking / Batch Produksi Dapur</h3>
                    <p class="text-xs text-gray-500">Pilih bahan mentah yang digunakan & komponen setengah jadi yang dihasilkan.</p>
                </div>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form wire:submit.prevent="save" class="p-6 space-y-6 overflow-y-auto flex-1 custom-scroll">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Kode Batch Produksi</label>
                        <input wire:model="production_code" type="text" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 font-mono" placeholder="PROD-YYYYMMDD-XXXX">
                        @error('production_code') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Produksi</label>
                        <div
                            wire:ignore
                            wire:key="production-date-picker-{{ $showModal ? 'open' : 'closed' }}"
                            x-data="{
                                init() {
                                    flatpickr(this.$refs.dateInput, {
                                        locale: 'id',
                                        dateFormat: 'Y-m-d',
                                        altInput: true,
                                        altFormat: 'j F Y',
                                        defaultDate: @js($production_date),
                                        animate: true,
                                        onChange: (selectedDates, dateStr) => {
                                            // false = jangan kirim request Livewire terpisah di sini (hindari race
                                            // condition dgn klik lain seperti tombol X) — nilai ikut terkirim saat submit.
                                            $wire.set('production_date', dateStr, false);
                                        }
                                    });
                                }
                            }"
                            x-init="init()"
                        >
                            <input x-ref="dateInput" type="text" placeholder="Pilih tanggal" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                        </div>
                        @error('production_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Step 1: Inputs (Bahan Mentah) -->
                <div class="space-y-3 bg-amber-50/40 p-4 rounded-xl border border-amber-100">
                    <div class="flex justify-between items-center border-b border-amber-200/60 pb-2">
                        <h4 class="text-sm font-bold text-amber-900 flex items-center gap-1.5">
                            <i data-lucide="box" class="w-4 h-4 text-amber-600"></i>
                            1. Bahan Mentah Yang Digunakan (Dipotong dari Stok Bahan)
                        </h4>
                        <button type="button" wire:click="addInputItem" class="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 hover:text-amber-800">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Bahan</span>
                        </button>
                    </div>

                    @foreach($inputItems as $index => $item)
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end p-3 bg-white rounded-lg border border-amber-200/60">
                            <div class="sm:col-span-5">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Bahan Baku Mentah</label>
                                <select wire:model.live="inputItems.{{ $index }}.ingredient_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                    <option value="">-- Pilih Bahan Baku --</option>
                                    @foreach($ingredients as $ing)
                                        <option value="{{ $ing->id }}">{{ $ing->name }} (Stok: {{ number_format($ing->stock, 2, ',', '.') }} {{ $ing->unit?->symbol }})</option>
                                    @endforeach
                                </select>
                                @error("inputItems.{$index}.ingredient_id") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Terpakai</label>
                                <input wire:model.live="inputItems.{{ $index }}.quantity" type="number" step="0.01" min="0.01" class="w-full text-xs border border-gray-200 rounded-lg p-2">
                                @error("inputItems.{$index}.quantity") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Estimasi HPP (Rp)</label>
                                <input wire:model.live="inputItems.{{ $index }}.unit_cost" type="number" step="100" readonly class="w-full text-xs border border-gray-100 bg-gray-50 rounded-lg p-2 text-gray-600">
                            </div>

                            <div class="sm:col-span-1 flex justify-end">
                                <button type="button" wire:click="removeInputItem({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Total Input Cost Banner -->
                <div class="bg-amber-100/70 p-3 rounded-lg flex justify-between items-center">
                    <span class="text-xs font-bold text-amber-900">Total Biaya Bahan Mentah Terpakai:</span>
                    <span class="text-base font-extrabold text-amber-800">Rp {{ number_format($this->totalInputCost, 0, ',', '.') }}</span>
                </div>

                <!-- Step 2: Outputs (Komponen / Item Setengah Jadi) -->
                <div class="space-y-3 bg-emerald-50/40 p-4 rounded-xl border border-emerald-100">
                    <div class="flex justify-between items-center border-b border-emerald-200/60 pb-2">
                        <div>
                            <h4 class="text-sm font-bold text-emerald-900 flex items-center gap-1.5">
                                <i data-lucide="package" class="w-4 h-4 text-emerald-600"></i>
                                2. Komponen / Item Setengah Jadi yang Dihasilkan
                            </h4>
                            <p class="text-xs text-emerald-700 mt-0.5">Stok komponen akan bertambah. Produk POS yang punya BOM akan memakai komponen ini saat checkout.</p>
                        </div>
                        <button type="button" wire:click="addOutputItem" class="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 hover:text-emerald-800 ml-4 flex-shrink-0">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Komponen</span>
                        </button>
                    </div>

                    @foreach($outputItems as $index => $item)
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end p-3 bg-white rounded-lg border border-emerald-200/60">
                            <div class="sm:col-span-5">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Komponen / Item Setengah Jadi</label>
                                <select wire:model.live="outputItems.{{ $index }}.component_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                    <option value="">-- Pilih Komponen --</option>
                                    @foreach($components as $comp)
                                        <option value="{{ $comp->id }}">{{ $comp->name }} (Stok: {{ number_format($comp->stock, 0, ',', '.') }} {{ $comp->unit?->symbol }})</option>
                                    @endforeach
                                </select>
                                @error("outputItems.{$index}.component_id") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Hasil (Qty / Pcs)</label>
                                <input wire:model.live="outputItems.{{ $index }}.quantity" type="number" min="1" step="1" class="w-full text-xs border border-gray-200 rounded-lg p-2">
                                @error("outputItems.{$index}.quantity") <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">HPP Terhitung / Item</label>
                                <div class="w-full text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-100 rounded-lg p-2">
                                    Rp {{ number_format($item['unit_cost'] ?? 0, 0, ',', '.') }}
                                </div>
                            </div>

                            <div class="sm:col-span-1 flex justify-end">
                                <button type="button" wire:click="removeOutputItem({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Produksi</label>
                    <textarea wire:model="note" rows="2" placeholder="Catatan proses produksi..." class="w-full text-sm border border-gray-200 rounded-lg p-2.5"></textarea>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="flex-1 py-2.5 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="save">Proses & Update Stok Komponen</span>
                        <span wire:loading wire:target="save" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Memproses...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Detail Production Modal -->
    @if($showDetailModal && $selectedProduction)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800">Detail Batch: {{ $selectedProduction->production_code }}</h3>
                    <p class="text-xs text-gray-500">Tanggal: {{ $selectedProduction->production_date->format('d M Y') }} &bull; Petugas: {{ $selectedProduction->user?->name ?? 'System' }}</p>
                </div>
                <button wire:click="$set('showDetailModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto custom-scroll">
                <!-- Inputs Table -->
                <div>
                    <h4 class="text-xs font-bold uppercase text-amber-800 mb-2">1. Bahan Mentah Terpakai:</h4>
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-amber-50 border-b text-amber-900 font-semibold">
                                <th class="py-2 px-3">Bahan Baku</th>
                                <th class="py-2 px-3 text-center">Jumlah</th>
                                <th class="py-2 px-3 text-right">Biaya Satuan</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($selectedProduction->inputs as $in)
                                <tr>
                                    <td class="py-2 px-3 font-medium">{{ $in->ingredient?->name }}</td>
                                    <td class="py-2 px-3 text-center">{{ number_format($in->quantity, 2, ',', '.') }} {{ $in->ingredient?->unit?->symbol }}</td>
                                    <td class="py-2 px-3 text-right">Rp {{ number_format($in->unit_cost, 0, ',', '.') }}</td>
                                    <td class="py-2 px-3 text-right font-semibold">Rp {{ number_format($in->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Outputs Table -->
                <div>
                    <h4 class="text-xs font-bold uppercase text-emerald-800 mb-2">2. Komponen / Item Setengah Jadi Dihasilkan:</h4>
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-emerald-50 border-b text-emerald-900 font-semibold">
                                <th class="py-2 px-3">Komponen</th>
                                <th class="py-2 px-3 text-center">Hasil</th>
                                <th class="py-2 px-3 text-right">HPP / Item</th>
                                <th class="py-2 px-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($selectedProduction->outputs as $out)
                                <tr>
                                    <td class="py-2 px-3 font-medium">{{ $out->getOutputName() }}</td>
                                    <td class="py-2 px-3 text-center font-bold text-emerald-700">{{ number_format($out->quantity, 0) }} {{ $out->getOutputUnit() }}</td>
                                    <td class="py-2 px-3 text-right">Rp {{ number_format($out->unit_cost, 0, ',', '.') }}</td>
                                    <td class="py-2 px-3 text-right font-semibold">Rp {{ number_format($out->subtotal, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="bg-gray-50 p-3 rounded-lg flex justify-between items-center font-bold text-sm">
                    <span>Total Biaya Produksi:</span>
                    <span class="text-emerald-700">Rp {{ number_format($selectedProduction->total_cost, 0, ',', '.') }}</span>
                </div>

                <div class="pt-2 flex justify-end">
                    <button wire:click="$set('showDetailModal', false)" class="px-5 py-2 text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg">Tutup</button>
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
