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
            <h1 class="text-2xl font-bold text-gray-800">Pembelian Stok</h1>
            <p class="text-gray-500">Catat transaksi pembelian bahan baku mentah atau produk siap jual dari supplier</p>
        </div>
        @can('create_purchases')
            <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2 shadow-sm transition-colors">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Tambah Pembelian
            </button>
        @endcan
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari no. faktur atau nama supplier..." 
                class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">No. Faktur</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Tanggal Beli</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Supplier</th>
                    <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Jumlah Item</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Petugas</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($purchases as $p)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 font-mono text-xs font-semibold text-primary-600">{{ $p->invoice_number }}</td>
                        <td class="px-6 py-4 text-gray-700">{{ $p->purchase_date->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $p->supplier_name ?: '-' }}</td>
                        <td class="px-6 py-4 text-center font-medium">{{ $p->items->count() }} Item</td>
                        <td class="px-6 py-4 font-bold text-gray-900">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-xs text-gray-500">{{ $p->user?->name ?? 'System' }}</td>
                        <td class="px-6 py-4 text-right">
                            <button wire:click="viewDetail({{ $p->id }})" title="Lihat Detail" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada riwayat pembelian stok</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($purchases->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" class="border-gray-200 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 px-3">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>pembelian</span>
                </div>
                <div>
                    {{ $purchases->links() }}
                </div>
            </div>
        @endif
    </div>

    <!-- Create Purchase Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl overflow-hidden border border-gray-100 max-h-[90vh] flex flex-col">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800">Form Pembelian Stok</h3>
                    <p class="text-xs text-gray-500">Isi data faktur dan item barang yang dibeli.</p>
                </div>
                <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form wire:submit.prevent="save" class="p-6 space-y-6 overflow-y-auto flex-1 custom-scroll">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">No. Faktur / Nota</label>
                        <input wire:model="invoice_number" type="text" class="w-full text-sm border border-gray-200 rounded-lg p-2.5 font-mono">
                        @error('invoice_number') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tanggal Pembelian</label>
                        <div
                            wire:ignore
                            wire:key="purchase-date-picker-{{ $showModal ? 'open' : 'closed' }}"
                            x-data="{
                                init() {
                                    flatpickr(this.$refs.dateInput, {
                                        locale: 'id',
                                        dateFormat: 'Y-m-d',
                                        altInput: true,
                                        altFormat: 'j F Y',
                                        defaultDate: @js($purchase_date),
                                        animate: true,
                                        onChange: (selectedDates, dateStr) => {
                                            // false = jangan kirim request Livewire terpisah di sini (hindari race
                                            // condition dgn klik lain seperti tombol X) — nilai ikut terkirim saat submit.
                                            $wire.set('purchase_date', dateStr, false);
                                        }
                                    });
                                }
                            }"
                            x-init="init()"
                        >
                            <input x-ref="dateInput" type="text" placeholder="Pilih tanggal" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                        </div>
                        @error('purchase_date') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Supplier / Vendor (Opsional)</label>
                        <input wire:model="supplier_name" type="text" placeholder="Contoh: Pasar Gede / Toko Daging" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                    </div>
                </div>

                <!-- Multi-Item Purchase Input -->
                <div class="space-y-3">
                    <div class="flex justify-between items-center border-b pb-2">
                        <h4 class="text-sm font-bold text-gray-700">Daftar Barang Dibeli</h4>
                        <button type="button" wire:click="addItem" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:text-primary-700">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Baris Barang</span>
                        </button>
                    </div>

                    @foreach($items as $index => $item)
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                            <div class="sm:col-span-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Tipe Item</label>
                                <select wire:model.live="items.{{ $index }}.item_type" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                    <option value="ingredient">Bahan Baku (Mentah)</option>
                                    <option value="product">Produk Jadi (Siap Jual)</option>
                                </select>
                            </div>

                            <div class="sm:col-span-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Pilih Barang</label>
                                @if($item['item_type'] === 'ingredient')
                                    <select wire:model="items.{{ $index }}.ingredient_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                        <option value="">-- Pilih Bahan Baku --</option>
                                        @foreach($ingredients as $ing)
                                            <option value="{{ $ing->id }}">{{ $ing->name }} ({{ $ing->unit }})</option>
                                        @endforeach
                                    </select>
                                @else
                                    <select wire:model="items.{{ $index }}.product_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                        <option value="">-- Pilih Produk --</option>
                                        @foreach($products as $prd)
                                            <option value="{{ $prd->id }}">{{ $prd->name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah</label>
                                <input wire:model.live="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Harga Beli Satuan (Rp)</label>
                                <input wire:model.live="items.{{ $index }}.unit_price" type="number" step="100" min="0" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                            </div>

                            <div class="sm:col-span-1 flex justify-end">
                                <button type="button" wire:click="removeItem({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Catatan Tambahan</label>
                    <textarea wire:model="note" rows="2" placeholder="Catatan transaksi..." class="w-full text-sm border border-gray-200 rounded-lg p-2.5"></textarea>
                </div>

                <!-- Total Cost Box -->
                <div class="bg-primary-50/50 p-4 rounded-xl border border-primary-100 flex justify-between items-center">
                    <span class="text-sm font-semibold text-primary-900">Total Pembelian:</span>
                    <span class="text-xl font-bold text-primary-700">Rp {{ number_format($this->totalAmount, 0, ',', '.') }}</span>
                </div>

                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg">Batal</button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="save" class="flex-1 py-2.5 text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg shadow-sm flex items-center justify-center gap-2">
                        <span wire:loading.remove wire:target="save">Simpan Pembelian & Update Stok</span>
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

    <!-- Detail Purchase Modal -->
    @if($showDetailModal && $selectedPurchase)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <div>
                    <h3 class="font-bold text-gray-800">Detail Faktur Pembelian: {{ $selectedPurchase->invoice_number }}</h3>
                    <p class="text-xs text-gray-500">Tanggal: {{ $selectedPurchase->purchase_date->format('d M Y') }} | Supplier: {{ $selectedPurchase->supplier_name ?: '-' }}</p>
                </div>
                <button wire:click="$set('showDetailModal', false)" class="text-gray-400 hover:text-gray-600">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <table class="w-full text-left text-sm border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b text-gray-600 font-semibold">
                            <th class="py-2 px-3">Tipe</th>
                            <th class="py-2 px-3">Nama Barang</th>
                            <th class="py-2 px-3 text-center">Jumlah</th>
                            <th class="py-2 px-3 text-right">Harga Satuan</th>
                            <th class="py-2 px-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($selectedPurchase->items as $item)
                            <tr>
                                <td class="py-2.5 px-3">
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded {{ $item->item_type === 'ingredient' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ $item->item_type === 'ingredient' ? 'Bahan Baku' : 'Produk Jadi' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-3 font-medium">
                                    {{ $item->item_type === 'ingredient' ? $item->ingredient?->name : $item->product?->name }}
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    {{ number_format($item->quantity, 2, ',', '.') }} {{ $item->item_type === 'ingredient' ? $item->ingredient?->unit : 'Pcs' }}
                                </td>
                                <td class="py-2.5 px-3 text-right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                                <td class="py-2.5 px-3 text-right font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t font-bold bg-gray-50">
                            <td colspan="4" class="py-3 px-3 text-right">Total Transaksi:</td>
                            <td class="py-3 px-3 text-right text-primary-700">Rp {{ number_format($selectedPurchase->total_amount, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>

                @if($selectedPurchase->note)
                    <div class="text-xs text-gray-500 bg-gray-50 p-3 rounded-lg border border-gray-100">
                        <strong>Catatan:</strong> {{ $selectedPurchase->note }}
                    </div>
                @endif

                <div class="pt-4 flex justify-end border-t">
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
