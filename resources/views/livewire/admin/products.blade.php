<div class="relative">
    <!-- Loading Overlay -->
    <div wire:loading wire:target="edit, openStockModal, create" class="absolute inset-0 bg-white/50 backdrop-blur-[1px] z-50 rounded-xl">
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
            <h1 class="text-2xl font-bold text-gray-800">Produk</h1>
            <p class="text-gray-500">Kelola produk menu</p>
        </div>
        @can('create_products')
            <button wire:click="create" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg flex items-center gap-2">
                <i data-lucide="plus" class="w-5 h-5"></i>
                Tambah Produk
            </button>
        @endcan
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6 p-4">
        <div class="flex gap-4">
            <div class="flex-1 relative">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari produk..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>
            <select wire:model.live="filterCategory" class="px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->slug }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Produk</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Kategori</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Harga</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-12 h-12 object-cover rounded-lg">
                                @else
                                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                        <i data-lucide="package" class="w-6 h-6 text-primary-600"></i>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium text-gray-800">{{ $product->name }}</p>
                                    <p class="text-sm text-gray-500">SKU: {{ $product->sku }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-gray-600">{{ $product->category->name }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800">{{ $product->formatted_price }}</td>
                        <td class="px-6 py-4 text-gray-600">
                            @if($product->track_stock)
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    {{ $product->stock }}
                                </span>
                            @else
                                <span class="text-gray-400 font-medium">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                @if($product->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 w-fit">Aktif</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 w-fit">Nonaktif</span>
                                @endif

                                @if($product->is_featured)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700 w-fit">
                                        <i data-lucide="star" class="w-3 h-3 fill-current"></i> Unggulan
                                    </span>
                                @endif

                                @if(!$product->track_stock)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-600 w-fit">
                                        <i data-lucide="infinity" class="w-3 h-3"></i> Unlimited
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- View Detail --}}
                                <a href="{{ route('admin.products.show', $product) }}" wire:navigate class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100" title="Lihat Detail">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                
                                {{-- Edit - with permission --}}
                                @can('edit_products')
                                    <button wire:click="edit({{ $product->id }})" class="p-2 text-gray-400 hover:text-primary-600 rounded-lg hover:bg-gray-100" title="Edit">
                                        <i data-lucide="edit" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                                
                                {{-- Delete - with permission --}}
                                @can('delete_products')
                                    <button 
                                        @click="$dispatch('confirm-action', {
                                            title: 'Hapus Produk',
                                            message: 'Apakah Anda yakin ingin menghapus produk {{ $product->name }}?',
                                            confirmText: 'Ya, Hapus',
                                            type: 'danger',
                                            action: { componentId: $wire.__instance.id, method: 'delete' },
                                            params: {{ $product->id }}
                                        })"
                                        class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-gray-100"
                                        title="Hapus"
                                    >
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i data-lucide="package" class="w-12 h-12 mx-auto mb-3 text-gray-300"></i>
                            <p>Belum ada produk</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($products->count() > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-600">
                    <span>Tampilkan</span>
                    <select wire:model.live="perPage" class="border-gray-200 rounded-lg text-sm focus:ring-primary-500 focus:border-primary-500 py-1.5 px-3">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>produk</span>
                </div>
                
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        @endif
    </div>



    <!-- Stock Management Modal -->
    @if($showStockModal && $selectedProduct)
        <div 
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="$wire.set('showStockModal', false)"
        >
            <div 
                class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] flex flex-col"
                x-data="{
                    type: 'add',
                    amount: 0,
                    note: '',
                    stock: {{ $selectedProduct->stock }},
                    formattedAmount: '',
                    
                    get prediction() {
                        let current = parseInt(this.stock);
                        let amt = parseInt(this.amount) || 0;
                        
                        if (this.type === 'add') return current + amt;
                        if (this.type === 'sub') return current - amt;
                        return amt;
                    },
                    
                    formatAmount(val) {
                        return new Intl.NumberFormat('id-ID').format(val || 0);
                    },
                    
                    parseAmount(str) {
                         return parseInt(String(str).replace(/\D/g, '')) || 0;
                    },
                    
                    updateAmount(e) {
                        let raw = this.parseAmount(e.target.value);
                        this.amount = raw;
                        this.formattedAmount = raw > 0 ? this.formatAmount(raw) : '';
                    },
                    
                    submit() {
                        if (this.amount < 1) return;
                        $wire.saveStock(this.type, this.amount, this.note);
                    }
                }"
            >
                <div class="p-6 border-b flex justify-between items-center flex-none">
                    <h3 class="text-xl font-bold text-gray-800">Atur Stok</h3>
                    <button type="button" wire:click="$set('showStockModal', false)" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1 custom-scroll">
                    <div class="mb-4 bg-gray-50 border border-gray-100 rounded-xl p-4 flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Stok Saat Ini</p>
                            <p class="text-3xl font-bold text-gray-800">{{ $selectedProduct->stock }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-400 mb-1">SKU</p>
                            <p class="font-mono text-sm font-medium text-gray-600">{{ $selectedProduct->sku }}</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jenis Penyesuaian</label>
                            <div class="grid grid-cols-3 gap-2">
                                <button 
                                    @click="type = 'add'" 
                                    :class="type === 'add' ? 'bg-white border-primary-500 text-primary-600 ring-1 ring-primary-500' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                    class="py-2 px-3 border rounded-lg text-sm font-medium transition-all"
                                >
                                    Tambah (+)
                                </button>
                                <button 
                                    @click="type = 'sub'" 
                                    :class="type === 'sub' ? 'bg-white border-red-500 text-red-600 ring-1 ring-red-500' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                    class="py-2 px-3 border rounded-lg text-sm font-medium transition-all"
                                >
                                    Kurang (-)
                                </button>
                                <button 
                                    @click="type = 'set'" 
                                    :class="type === 'set' ? 'bg-white border-blue-500 text-blue-600 ring-1 ring-blue-500' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50'"
                                    class="py-2 px-3 border rounded-lg text-sm font-medium transition-all"
                                >
                                    Setel (=)
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                <span x-show="type === 'add'">Jumlah (Penambahan)</span>
                                <span x-show="type === 'sub'">Jumlah (Pengurangan)</span>
                                <span x-show="type === 'set'">Stok Baru</span>
                            </label>
                            
                            <input 
                                type="text"
                                inputmode="numeric"
                                x-model="formattedAmount"
                                @input="updateAmount($event)"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="0"
                            >
                            <p x-show="amount < 1 && formattedAmount !== ''" class="text-red-500 text-sm mt-1">Jumlah penyesuaian minimal 1.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Catatan 
                                <span x-show="type !== 'add'" class="text-red-500">*</span>
                                <span x-show="type === 'add'" class="text-gray-400 text-xs font-normal">(Optional)</span>
                            </label>
                            <input 
                                type="text" 
                                x-model="note" 
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                                :placeholder="type !== 'add' ? 'Wajib diisi (misal: Barang rusak)' : 'Contoh: Restock supplier'"
                            >
                        </div>
                        
                        <div class="pt-2">
                            <p class="text-sm text-gray-600 flex justify-between">
                                <span>Prediksi Stok Akhir (Belum Disimpan):</span>
                                <span class="font-bold" x-text="prediction"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t bg-gray-50 flex justify-end gap-3 rounded-b-2xl flex-none">
                    <button type="button" wire:click="$set('showStockModal', false)" class="px-4 py-2 bg-white text-gray-700 font-medium rounded-lg border border-gray-200 hover:bg-gray-50">
                        Batal
                    </button>
                    <button 
                        type="button" 
                        @click="submit()" 
                        wire:loading.attr="disabled"
                        class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <span wire:loading.remove wire:target="saveStock">Simpan Perubahan</span>
                        <span wire:loading wire:target="saveStock" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Menyimpan...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal -->
    @if($showModal)
        <div 
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            x-data="{ localModifierGroups: @js($selectedModifierGroups) }"
            @keydown.escape.window="$wire.set('showModal', false)"
        >
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="p-6 border-b flex justify-between items-center flex-none">
                    <h3 class="text-xl font-bold text-gray-800">{{ $editingId ? 'Edit Produk' : 'Tambah Produk' }}</h3>
                    <button type="button" wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Kategori *</label>
                            <select wire:model="category_id" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                                <option value="">Pilih Kategori</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                            @error('category_id') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">SKU *</label>
                            <input type="text" wire:model="sku" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary-500 @error('sku') border-red-500 @else border-gray-200 @enderror" placeholder="PRD241229XXXX">
                            @error('sku') 
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @else
                                @if(!$editingId)
                                    <p class="text-gray-500 text-xs mt-1">SKU otomatis, bisa diubah manual</p>
                                @endif
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nama Produk *</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500" placeholder="Bakso Biasa">
                        @error('name') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Deskripsi</label>
                        <textarea wire:model="description" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div x-data="moneyInput({{ $price }}, 'price')">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga Jual *</label>
                            <input 
                                type="text" 
                                inputmode="numeric"
                                x-model="formatted"
                                @input="onInput($event)"
                                @blur="syncToWire()"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="0"
                            >
                            @error('price') <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div x-data="moneyInput({{ $cost_price }}, 'cost_price')">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Harga Modal</label>
                            <input 
                                type="text" 
                                inputmode="numeric"
                                x-model="formatted"
                                @input="onInput($event)"
                                @blur="syncToWire()"
                                class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500"
                                placeholder="0"
                            >
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gambar</label>
                        <input 
                            type="file" 
                            wire:model="image" 
                            accept=".png,.jpg,.jpeg,.svg,image/png,image/jpeg,image/svg+xml" 
                            class="w-full px-4 py-2 border rounded-lg @error('image') border-red-500 @else border-gray-200 @enderror"
                        >
                        @error('image') 
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @else
                            <p class="text-gray-500 text-xs mt-1">Format: PNG, JPG, JPEG, SVG. Maks 2MB. Otomatis dikonversi ke WebP.</p>
                        @enderror
                        
                        {{-- Image Preview --}}
                        @if($image)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ $image->temporaryUrl() }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                                <span class="text-sm text-green-600">Gambar baru siap diupload</span>
                            </div>
                        @elseif($existingImage)
                            <div class="mt-2 flex items-center gap-3">
                                <img src="{{ asset('storage/' . $existingImage) }}" class="w-20 h-20 object-cover rounded-lg border border-gray-200">
                                <button 
                                    type="button"
                                    wire:click="removeImage"
                                    wire:confirm="Hapus gambar ini?"
                                    class="px-3 py-1.5 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors flex items-center gap-1"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    Hapus gambar
                                </button>
                            </div>
                        @endif
                    </div>
                    <!-- BOM (Bill of Materials) Section -->
                    <div class="space-y-3 bg-blue-50/60 p-4 rounded-xl border border-blue-100">
                        <div class="flex justify-between items-center border-b border-blue-200/60 pb-2">
                            <div>
                                <h4 class="text-sm font-bold text-blue-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    Komposisi Komponen (BOM)
                                </h4>
                                <p class="text-xs text-blue-700">Jika diisi, checkout POS akan mengurangi stok komponen terkait.</p>
                            </div>
                            <button type="button" wire:click="addBomItem" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-700 hover:text-blue-800">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Tambah Komponen</span>
                            </button>
                        </div>

                        @foreach($bomItems as $bIndex => $bItem)
                            <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end p-2 bg-white rounded-lg border border-blue-200/60">
                                <div class="sm:col-span-7">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Komponen Setengah Jadi</label>
                                    <select wire:model="bomItems.{{ $bIndex }}.component_id" class="w-full text-xs border border-gray-200 rounded-lg p-2 bg-white">
                                        <option value="">-- Pilih Komponen --</option>
                                        @foreach($components as $comp)
                                            <option value="{{ $comp->id }}">{{ $comp->name }} (Stok: {{ number_format($comp->stock, 0, ',', '.') }} {{ $comp->unit }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="sm:col-span-4">
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Qty per 1 Produk</label>
                                    <input type="number" step="0.01" min="0.01" wire:model="bomItems.{{ $bIndex }}.quantity" class="w-full text-xs border border-gray-200 rounded-lg p-2">
                                </div>
                                <div class="sm:col-span-1 flex justify-end">
                                    <button type="button" wire:click="removeBomItem({{ $bIndex }})" class="p-1.5 text-red-500 hover:bg-red-50 rounded-lg">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Modifier Groups</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($modifierGroups as $mg)
                                <button 
                                    type="button"
                                    @click="localModifierGroups.includes({{ $mg->id }}) ? localModifierGroups = localModifierGroups.filter(id => id !== {{ $mg->id }}) : localModifierGroups.push({{ $mg->id }})"
                                    :class="localModifierGroups.includes({{ $mg->id }}) ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 hover:bg-gray-50'"
                                    class="inline-flex items-center gap-2 px-3 py-2 border rounded-lg cursor-pointer transition-all text-sm"
                                >
                                    <span 
                                        :class="localModifierGroups.includes({{ $mg->id }}) ? 'bg-primary-500' : 'bg-white border-2 border-gray-300'"
                                        class="w-4 h-4 rounded flex items-center justify-center transition-all"
                                    >
                                        <svg x-show="localModifierGroups.includes({{ $mg->id }})" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    {{ $mg->name }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="is_active" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Aktif</span>
                        </label>
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="is_featured" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Unggulan</span>
                        </label>
                        @can('manage_unlimited_stock')
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" wire:model="is_unlimited" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                <span class="text-sm text-gray-700">Stok Unlimited</span>
                            </label>
                        @endcan
                    </div>
                    @if(!$is_unlimited && !$editingId)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok Awal</label>
                            <input type="number" wire:model="stock" class="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-primary-500">
                        </div>
                    @endif
                </div>
                
                <!-- Footer -->
                <div class="p-6 border-t bg-gray-50 flex gap-3 flex-none rounded-b-2xl">
                    <button type="button" wire:click="$set('showModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-lg transition-colors">Batal</button>
                    <button 
                        type="button" 
                        @click="$wire.set('selectedModifierGroups', localModifierGroups); $wire.save()"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <span wire:loading.remove wire:target="save">Simpan</span>
                        <span wire:loading wire:target="save">Menyimpan...</span>
                    </button>
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
