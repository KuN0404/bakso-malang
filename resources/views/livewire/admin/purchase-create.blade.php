<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="save" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
        <div class="sticky top-[40vh] flex flex-col items-center justify-center w-full gap-2">
            <svg class="animate-spin h-8 w-8 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-primary-600">Memproses...</span>
        </div>
    </div>

    <!-- Breadcrumb & Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="hover:text-primary-600">Pembelian Stok</a>
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
            <span class="text-gray-900">Tambah Pembelian</span>
        </div>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Tambah Pembelian Stok</h1>
                <p class="text-gray-500">Isi data faktur dan item barang yang dibeli.</p>
            </div>
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="px-4 py-2 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50 flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    <form wire:submit.prevent="save" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
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
                                    // condition dgn aksi lain) — nilai ikut terkirim saat submit.
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
                <label class="block text-xs font-semibold text-gray-700 mb-1">Supplier / Vendor (Opsional)</label>
                <select wire:model="supplier_id" class="w-full text-sm border border-gray-200 rounded-lg p-2.5">
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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
                            <select wire:model="items.{{ $index }}.ingredient_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white @error('items.'.$index.'.ingredient_id') border-red-400 @enderror">
                                <option value="">-- Pilih Bahan Baku --</option>
                                @foreach($ingredients as $ing)
                                    <option value="{{ $ing->id }}">[{{ $ing->code }}] {{ $ing->name }} ({{ $ing->unit?->symbol }})</option>
                                @endforeach
                            </select>
                            @error('items.'.$index.'.ingredient_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        @else
                            <select wire:model="items.{{ $index }}.product_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white @error('items.'.$index.'.product_id') border-red-400 @enderror">
                                <option value="">-- Pilih Produk --</option>
                                @foreach($products as $prd)
                                    <option value="{{ $prd->id }}">[{{ $prd->sku }}] {{ $prd->name }}</option>
                                @endforeach
                            </select>
                            @error('items.'.$index.'.product_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        @endif
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah</label>
                        <input wire:model.live.debounce.300ms="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                    </div>

                    <div class="sm:col-span-2" x-data="moneyInput({{ $item['unit_price'] ?? 0 }}, 'items.{{ $index }}.unit_price')" wire:key="unit-price-{{ $index }}">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Harga Beli Satuan (Rp)</label>
                        <input
                            type="text"
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @input.debounce.300ms="syncToWire()"
                            @blur="syncToWire()"
                            class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white"
                            placeholder="0"
                        >
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
            <a href="{{ route('admin.purchases.index') }}" wire:navigate class="flex-1 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg text-center">Batal</a>
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

@script
<script>
    lucide.createIcons();
</script>
@endscript
