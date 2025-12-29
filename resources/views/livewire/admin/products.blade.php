<div>
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
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
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
                                {{ $product->stock }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($product->is_active)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- View Detail --}}
                                <button wire:click="view({{ $product->id }})" class="p-2 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100" title="Lihat Detail">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </button>
                                
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
        @if($products->hasPages())
            <div class="px-6 py-4 border-t">{{ $products->links() }}</div>
        @endif
    </div>

    <!-- View Detail Modal -->
    @if($showViewModal && $selectedProduct)
        <div 
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            @keydown.escape.window="$wire.set('showViewModal', false)"
        >
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="p-6 border-b flex justify-between items-center flex-none">
                    <h3 class="text-xl font-bold text-gray-800">Detail Produk</h3>
                    <button type="button" wire:click="$set('showViewModal', false)" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                    <!-- Image -->
                    @if($selectedProduct->image)
                        <div class="flex justify-center">
                            <img src="{{ asset('storage/' . $selectedProduct->image) }}" class="w-40 h-40 object-cover rounded-xl border border-gray-200" alt="{{ $selectedProduct->name }}">
                        </div>
                    @endif
                    
                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase">Nama Produk</p>
                            <p class="font-semibold text-gray-800">{{ $selectedProduct->name }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">SKU</p>
                            <p class="font-mono text-gray-700">{{ $selectedProduct->sku }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Kategori</p>
                            <p class="text-gray-700">{{ $selectedProduct->category?->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Harga Jual</p>
                            <p class="font-bold text-primary-600">Rp {{ number_format($selectedProduct->price, 0, ',', '.') }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Harga Modal</p>
                            @can('view_financial_reports')
                                <p class="text-gray-700">Rp {{ number_format($selectedProduct->cost_price, 0, ',', '.') }}</p>
                            @else
                                <p class="text-gray-400 italic">Tidak memiliki akses</p>
                            @endcan
                        </div>
                    </div>
                    
                    @if($selectedProduct->description)
                        <div>
                            <p class="text-xs text-gray-500 uppercase">Deskripsi</p>
                            <p class="text-gray-700">{{ $selectedProduct->description }}</p>
                        </div>
                    @endif
                    
                    <!-- Status Badges -->
                    <div class="flex flex-wrap gap-2">
                        @if($selectedProduct->is_active)
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">Aktif</span>
                        @else
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Nonaktif</span>
                        @endif
                        @if($selectedProduct->is_featured)
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-700">Unggulan</span>
                        @endif
                        @if($selectedProduct->track_stock)
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700">Stok: {{ $selectedProduct->stock }}</span>
                        @endif
                    </div>
                    
                    <!-- Modifier Groups -->
                    @if($selectedProduct->modifierGroups->isNotEmpty())
                        <div>
                            <p class="text-xs text-gray-500 uppercase mb-2">Modifier Groups</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($selectedProduct->modifierGroups as $mg)
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-medium bg-primary-100 text-primary-700">{{ $mg->name }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
                
                <!-- Footer -->
                <div class="p-4 border-t bg-gray-50 flex-none">
                    <button type="button" wire:click="$set('showViewModal', false)" class="w-full py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-medium rounded-lg transition-colors">
                        Tutup
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
                        <label class="inline-flex items-center gap-2">
                            <input type="checkbox" wire:model="track_stock" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                            <span class="text-sm text-gray-700">Lacak Stok</span>
                        </label>
                    </div>
                    @if($track_stock)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Stok</label>
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
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors"
                    >Simpan</button>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    lucide.createIcons();
    Livewire.hook('morph.updated', () => lucide.createIcons());
</script>
@endscript
