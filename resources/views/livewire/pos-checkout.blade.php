<div class="fixed inset-0 w-full flex bg-gray-100 overflow-hidden" style="height: 100dvh;"
    x-data="{
        showProductModal: false, 
        selectedProduct: null, 
        showCartMobile: false,
        // Modifier selection
        showModifierModal: false,
        modifierProduct: null,
        selectedModifiers: {},
        modifierTotal: 0,
        
        openModifierModal(product) {
            this.modifierProduct = product;
            this.selectedModifiers = {};
            this.modifierTotal = 0;
            // Set defaults for single selection groups (first option)
            if (product.modifier_groups) {
                product.modifier_groups.forEach(group => {
                    if (group.selection_type === 'single' && group.modifiers && group.modifiers.length > 0) {
                        this.selectModifier(group.id, group.modifiers[0], 'single');
                    }
                });
            }
            this.showModifierModal = true;
        },
        selectModifier(groupId, modifier, selectionType) {
            if (selectionType === 'single') {
                this.selectedModifiers[groupId] = [modifier];
            } else {
                if (!this.selectedModifiers[groupId]) {
                    this.selectedModifiers[groupId] = [];
                }
                const index = this.selectedModifiers[groupId].findIndex(m => m.id === modifier.id);
                if (index > -1) {
                    this.selectedModifiers[groupId].splice(index, 1);
                } else {
                    this.selectedModifiers[groupId].push(modifier);
                }
            }
            this.calculateModifierTotal();
        },
        isModifierSelected(groupId, modifierId) {
            if (!this.selectedModifiers[groupId]) return false;
            return this.selectedModifiers[groupId].some(m => m.id === modifierId);
        },
        calculateModifierTotal() {
            let total = 0;
            Object.values(this.selectedModifiers).forEach(modifiers => {
                modifiers.forEach(m => {
                    total += Number(m.price_adjustment) || 0;
                });
            });
            this.modifierTotal = total;
        },
        formatRupiah(num) {
            return new Intl.NumberFormat('id-ID').format(Number(num) || 0);
        },
        getTotalPrice() {
            return (Number(this.modifierProduct?.price) || 0) + (Number(this.modifierTotal) || 0);
        },
        getModifiersForCart() {
            const result = {};
            Object.values(this.selectedModifiers).forEach(modifiers => {
                modifiers.forEach(m => {
                    result[m.id] = { name: m.name, price: Number(m.price_adjustment) || 0 };
                });
            });
            return result;
        },
        addWithModifiers() {
            if (this.modifierProduct) {
                $wire.addToCart(this.modifierProduct.id, this.getModifiersForCart());
                this.showModifierModal = false;
                this.modifierProduct = null;
                this.selectedModifiers = {};
            }
        }
    }"
    @keydown.f1.window.prevent="$wire.openPaymentModal()"
    @keydown.f2.window.prevent="$wire.processPayment()"
    @keydown.f3.window.prevent="if(confirm('Hapus semua item?')) $wire.clearCart()"
    @keydown.f4.window.prevent="$wire.openCloseShiftModal()"
    @keydown.escape.window="$wire.closePaymentModal(); $wire.closeReceiptModal(); $wire.set('showCloseShiftModal', false); showModifierModal = false">
    
    <!-- Unclosed Previous Shift Blocking Modal -->
    @if($this->unclosedPreviousShift && !$showUnclosedShiftModal)
        <div class="fixed inset-0 bg-black/70 z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
                <div class="p-6 border-b bg-red-50 rounded-t-2xl">
                    <div class="flex items-center gap-3 text-red-600">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i data-lucide="alert-triangle" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">Shift Belum Ditutup</h3>
                            <p class="text-sm text-red-500">Anda harus menutup shift sebelumnya</p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 space-y-4">
                    <div class="bg-gray-50 rounded-xl p-4">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Tanggal Shift</p>
                                <p class="font-bold text-gray-800">{{ $this->unclosedPreviousShift->started_at->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Jam Mulai</p>
                                <p class="font-bold text-gray-800">{{ $this->unclosedPreviousShift->started_at->format('H:i') }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total Transaksi</p>
                                <p class="font-bold text-gray-800">{{ $this->unclosedPreviousShift->transactions->count() }} transaksi</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total Penjualan</p>
                                <p class="font-bold text-primary-600">Rp {{ number_format($this->unclosedPreviousShift->transactions->sum('total'), 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4">
                        <p class="text-yellow-800 text-sm flex items-start gap-2">
                            <i data-lucide="info" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                            <span>POS akan diblokir sampai shift ini ditutup. Silakan tutup shift atau hubungi Super Admin.</span>
                        </p>
                    </div>
                </div>
                
                <div class="p-6 border-t bg-gray-50 rounded-b-2xl">
                    <button 
                        wire:click="openClosePreviousShiftModal"
                        class="w-full py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <i data-lucide="clock" class="w-5 h-5"></i>
                        Tutup Shift Sekarang
                    </button>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Unclosed Shift Close Form Modal -->
    @if($showUnclosedShiftModal)
        <div class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-6 border-b flex-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="clock" class="w-6 h-6 text-red-600"></i>
                            Tutup Shift {{ \App\Models\Shift::find($unclosedShiftId)?->started_at?->format('d/m/Y') }}
                        </h3>
                    </div>
                    <p class="text-sm text-red-500 mt-1">Shift ini akan ditandai sebagai "Ditutup Terlambat"</p>
                </div>
                <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                    <!-- Summary -->
                    @php $unclosedShift = \App\Models\Shift::with('transactions')->find($unclosedShiftId); @endphp
                    @if($unclosedShift)
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-sm text-blue-600">Penjualan Shift Ini</p>
                            <p class="text-2xl font-bold text-blue-700">Rp {{ number_format($unclosedShift->transactions->sum('total'), 0, ',', '.') }}</p>
                            <p class="text-sm text-blue-600 mt-1">{{ $unclosedShift->transactions->count() }} transaksi</p>
                        </div>
                    @endif

                    <!-- Opening Cash -->
                    <div x-data="moneyInput({{ $openingCash }}, 'openingCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modal Awal (Cash)</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                    </div>

                    <!-- Expenses -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Pengeluaran</label>
                            <button type="button" wire:click="addExpense" class="text-primary-600 text-sm font-medium flex items-center gap-1">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah
                            </button>
                        </div>
                        @foreach($expenses as $index => $expense)
                            <div class="flex gap-2 mb-2" wire:key="prev-expense-{{ $index }}">
                                <input type="text" wire:model="expenses.{{ $index }}.description" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Keterangan">
                                <input type="number" wire:model="expenses.{{ $index }}.amount" class="w-28 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Jumlah">
                                <button wire:click="removeExpense({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <!-- Actual Cash -->
                    <div x-data="moneyInput({{ $actualCash }}, 'actualCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uang Fisik di Laci</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea wire:model="closeNotes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Catatan opsional"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t bg-gray-50 flex gap-3 flex-none">
                    <button wire:click="$set('showUnclosedShiftModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Kembali</button>
                    <button wire:click="closePreviousShift" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Tutup Shift
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Left Panel: Products -->
    <div class="flex-1 flex flex-col bg-gray-50 min-w-0 overflow-x-hidden">
        <!-- Header -->
        <header class="bg-white px-6 py-3 border-b flex justify-between items-center sticky top-0 z-20">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i data-lucide="soup" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="font-bold text-gray-800 leading-tight">Bakso Malang</h1>
                    <p class="text-xs text-gray-500">Point of Sale</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if($this->todayShift)
                    <!-- History Buttons Group -->
                    <div class="flex items-center">
                        <button wire:click="openHistoryModal" class="flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 rounded-l-lg text-blue-700 transition-colors border border-blue-200 border-r-0" title="Riwayat Transaksi">
                            <i data-lucide="history" class="w-4 h-4 text-blue-600"></i>
                            <span class="bg-blue-200 text-blue-800 text-xs py-0.5 px-2 rounded-full font-bold">
                                {{ $this->todayTransactions->count() }}
                            </span>
                        </button>
                        <button wire:click="openReturnHistoryModal" class="flex items-center gap-2 px-3 py-2 bg-orange-50 hover:bg-orange-100 rounded-r-lg text-orange-700 transition-colors border border-orange-200" title="Riwayat Retur">
                            <i data-lucide="list" class="w-4 h-4 text-orange-600"></i>
                            <span class="bg-orange-200 text-orange-800 text-xs py-0.5 px-2 rounded-full font-bold">
                                {{ $this->todayReturns->count() }}
                            </span>
                        </button>
                    </div>
                    
                    <!-- Return Action Button -->
                    <button wire:click="openReturnModal" class="flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 rounded-lg text-red-600 transition-colors border border-red-200 shadow-sm">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span class="text-sm font-bold uppercase tracking-wide">Retur</span>
                    </button>
                @endif

                @if($this->todayShift && $this->todayShift->status === 'open')
                    <button wire:click="openCloseShiftModal" class="flex items-center gap-2 px-3 py-2 bg-red-50 hover:bg-red-50 text-red-600 rounded-lg transition-colors border border-red-100">
                        <i data-lucide="power" class="w-4 h-4"></i>
                        <span class="text-sm font-medium hidden md:inline">Tutup Shift (F4)</span>
                    </button>
                @endif
                
                <div class="h-6 w-px bg-gray-200 mx-1"></div>

                <a href="{{ route('admin.dashboard') }}" class="p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Dashboard">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                </a>

                <div class="flex items-center gap-2 text-sm text-gray-600 bg-gray-50 px-3 py-1.5 rounded-lg border border-gray-100">
                    <i data-lucide="user" class="w-4 h-4 text-gray-400"></i>
                    <span class="font-medium truncate max-w-[100px]">{{ auth()->user()->name }}</span>
                </div>
            </div>
        </header>

        <!-- Search & Categories -->
        <div class="px-6 py-4 bg-white border-b space-y-4">
            <!-- Search -->
            <div class="relative">
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="searchQuery"
                    placeholder="Cari produk... (ketik untuk mencari)"
                    class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                >
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
            </div>

            <!-- Categories -->
            <div class="flex gap-2 overflow-x-auto pb-2 custom-scroll">
                <button 
                    wire:click="selectCategory(null)"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all flex items-center gap-2 {{ !$selectedCategoryId ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <i data-lucide="grid-3x3" class="w-4 h-4"></i>
                    Semua
                </button>
                @foreach($this->categories as $category)
                    <button 
                        wire:click="selectCategory({{ $category->id }})"
                        class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all flex items-center gap-2 {{ $selectedCategoryId === $category->id ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        <i data-lucide="tag" class="w-4 h-4"></i>
                        {{ $category->name }}
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Products Grid -->
        <div class="flex-1 overflow-y-auto p-4 md:p-6 custom-scroll pb-24 lg:pb-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @forelse($this->products as $product)
                    @php
                        $productData = [
                            'id' => $product->id,
                            'name' => $product->name,
                            'price' => $product->price,
                            'image' => $product->image ? asset('storage/' . $product->image) : null,
                            'modifier_groups' => $product->modifierGroups->map(fn($g) => [
                                'id' => $g->id,
                                'name' => $g->name,
                                'selection_type' => $g->selection_type,
                                'is_required' => $g->is_required,
                                'modifiers' => $g->activeModifiers->map(fn($m) => [
                                    'id' => $m->id,
                                    'name' => $m->name,
                                    'price_adjustment' => $m->price_adjustment,
                                ])->toArray()
                            ])->toArray()
                        ];
                        $hasModifiers = $product->modifierGroups->count() > 0;
                        $isOutOfStock = $product->track_stock && $product->stock <= 0;
                    @endphp
                    <div 
                        @if(!$isOutOfStock)
                            @if($hasModifiers)
                                @click="openModifierModal({{ json_encode($productData) }})"
                            @else
                                wire:click="addToCart({{ $product->id }}, [])"
                            @endif
                        @endif
                        wire:key="product-{{ $product->id }}"
                        class="bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group border border-gray-100 hover:border-primary-300 relative {{ $isOutOfStock ? 'opacity-60 grayscale cursor-not-allowed hover:border-gray-200 hover:shadow-none' : '' }}"
                    >
                        <!-- Badges -->
                        <div class="absolute top-2 right-2 flex flex-col gap-1 items-end z-10">
                            @if($product->is_featured)
                                <div class="bg-yellow-400 text-yellow-900 text-xs px-2 py-0.5 rounded-full flex items-center gap-1 font-bold shadow-sm">
                                    <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    Unggulan
                                </div>
                            @endif
                            @if($hasModifiers)
                                <div class="bg-primary-500 text-white text-xs px-2 py-0.5 rounded-full flex items-center gap-1 shadow-sm">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Opsi
                                </div>
                            @endif
                        </div>

                        <!-- Image -->
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-24 object-cover rounded-lg mb-3">
                        @else
                            <div class="w-full h-24 bg-gradient-to-br from-primary-100 to-primary-200 rounded-lg mb-3 flex items-center justify-center">
                                <i data-lucide="package" class="w-8 h-8 text-primary-500"></i>
                            </div>
                        @endif
                        
                        <!-- Out of Stock Overlay -->
                        @if($isOutOfStock)
                            <div class="absolute inset-0 bg-white/50 z-[5] rounded-xl flex items-center justify-center backdrop-blur-[1px]">
                                <div class="bg-red-600 text-white px-3 py-1 rounded-lg font-bold text-sm shadow-md transform -rotate-12 border-2 border-white">
                                    HABIS
                                </div>
                            </div>
                        @endif

                        <h3 class="font-medium text-gray-800 group-hover:text-primary-600 transition-colors line-clamp-2">
                            {{ $product->name }}
                        </h3>
                        <p class="text-primary-600 font-bold mt-1">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                        @if($product->track_stock)
                            <p class="text-xs mt-1 flex items-center gap-1 {{ $product->stock <= 0 ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                <i data-lucide="box" class="w-3 h-3"></i>
                                Stok: {{ $product->stock }}
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 text-gray-300"></i>
                        <p>Tidak ada produk ditemukan</p>
                        <p class="text-sm mt-2">Pastikan ada produk aktif di database</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Modifier Selection Modal -->
        <div 
            x-show="showModifierModal" 
            x-cloak
            class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4"
            @click.self="showModifierModal = false"
        >
            <div 
                x-show="showModifierModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[85vh] flex flex-col"
                @click.stop
            >
                <!-- Header -->
                <div class="p-4 border-b flex justify-between items-center flex-none">
                    <div class="flex items-center gap-3">
                        <template x-if="modifierProduct && modifierProduct.image">
                            <img :src="modifierProduct.image" class="w-12 h-12 object-cover rounded-lg">
                        </template>
                        <template x-if="modifierProduct && !modifierProduct.image">
                            <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </div>
                        </template>
                        <div>
                            <h3 class="font-bold text-gray-800" x-text="modifierProduct?.name"></h3>
                            <p class="text-sm text-primary-600 font-medium">Rp <span x-text="formatRupiah(modifierProduct?.price)"></span></p>
                        </div>
                    </div>
                    <button @click="showModifierModal = false" class="text-gray-400 hover:text-gray-600 p-2 hover:bg-gray-100 rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                
                <!-- Modifier Options -->
                <div class="p-4 overflow-y-auto flex-1 custom-scroll space-y-4">
                    <template x-for="group in modifierProduct?.modifier_groups || []" :key="group.id">
                        <div class="bg-gray-50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-800" x-text="group.name"></h4>
                                <span class="text-xs px-2 py-1 rounded-full" :class="group.selection_type === 'single' ? 'bg-blue-100 text-blue-600' : 'bg-green-100 text-green-600'" x-text="group.selection_type === 'single' ? 'Pilih 1' : 'Pilih banyak'"></span>
                            </div>
                            <div class="space-y-2">
                                <template x-for="modifier in group.modifiers" :key="modifier.id">
                                    <button 
                                        type="button"
                                        @click="selectModifier(group.id, modifier, group.selection_type)"
                                        :class="isModifierSelected(group.id, modifier.id) ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="w-full flex items-center justify-between px-4 py-3 border rounded-lg transition-all"
                                    >
                                        <div class="flex items-center gap-3">
                                            <span 
                                                :class="isModifierSelected(group.id, modifier.id) ? 'bg-primary-500' : 'border-2 border-gray-300 bg-white'"
                                                class="w-5 h-5 rounded-full flex items-center justify-center transition-all flex-shrink-0"
                                            >
                                                <svg x-show="isModifierSelected(group.id, modifier.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span class="font-medium" x-text="modifier.name"></span>
                                        </div>
                                        <span :class="modifier.price_adjustment > 0 ? 'text-primary-600' : 'text-gray-400'" class="text-sm font-medium">
                                            <template x-if="Number(modifier.price_adjustment) > 0">
                                                <span>+Rp <span x-text="formatRupiah(modifier.price_adjustment)"></span></span>
                                            </template>
                                            <template x-if="modifier.price_adjustment == 0">
                                                <span>Gratis</span>
                                            </template>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
                
                <!-- Footer -->
                <div class="p-4 border-t bg-gray-50 flex-none rounded-b-2xl">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-gray-600">Total Harga</span>
                        <span class="text-xl font-bold text-primary-600">Rp <span x-text="formatRupiah(getTotalPrice())"></span></span>
                    </div>
                    <button 
                        @click="addWithModifiers()"
                        class="w-full py-4 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        Tambah ke Keranjang
                    </button>
                </div>
            </div>
        </div>

        <!-- Shortcut hints -->
        <div class="hidden lg:flex bg-gray-800 text-white px-6 py-2 gap-6 text-xs">
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">F1</kbd> Bayar</span>
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">F2</kbd> Proses</span>
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">F3</kbd> Hapus</span>
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">F4</kbd> Tutup Shift</span>
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">Esc</kbd> Tutup Modal</span>
        </div>
        <!-- Mobile Sticky Bottom Bar -->
        <div class="lg:hidden bg-white border-t p-4 px-6 flex justify-between items-center sticky bottom-0 z-30 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.1)]">
            <div>
                <p class="text-xs text-gray-500">Total Tagihan</p>
                <p class="text-xl font-bold text-primary-600">Rp {{ number_format($this->total, 0, ',', '.') }}</p>
            </div>
            <button 
                @click="showCartMobile = true"
                class="flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 transition-all active:scale-95"
            >
                <div class="relative">
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    @if(count($cart) > 0)
                        <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] w-4 h-4 rounded-full flex items-center justify-center font-bold border border-white">
                            {{ count($cart) }}
                        </span>
                    @endif
                </div>
                <span>Keranjang</span>
            </button>
        </div>
    </div>

    <!-- Mobile Cart Backdrop -->
    <div 
        x-show="showCartMobile"
        x-transition.opacity
        @click="showCartMobile = false"
        class="fixed inset-0 z-40 bg-black/50 lg:hidden"
    ></div>

    <!-- Right Panel: Cart -->
    <div 
        :class="showCartMobile ? 'translate-x-0' : 'translate-x-full lg:translate-x-0'"
        class="fixed inset-y-0 right-0 z-50 w-full md:w-96 bg-white border-l flex flex-col transform transition-transform duration-300 lg:static lg:transform-none shadow-2xl lg:shadow-none"
    >
        <!-- Cart Header -->
        <div class="p-4 border-b">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                    <button @click="showCartMobile = false" class="lg:hidden text-gray-500 hover:text-gray-700 mr-2">
                        <i data-lucide="arrow-left" class="w-6 h-6"></i>
                    </button>
                    <i data-lucide="shopping-cart" class="w-5 h-5"></i>
                    Keranjang
                </h2>
                @if(count($cart) > 0)
                    <button wire:click="clearCart" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Hapus Semua
                    </button>
                @endif
            </div>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto p-4 space-y-3 custom-scroll">
            @forelse($cart as $cartKey => $item)
                <div class="bg-gray-50 rounded-lg p-3" wire:key="cart-{{ $cartKey }}">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-medium text-gray-800">{{ $item['product_name'] }}</h4>
                            @if(!empty($item['modifiers']))
                                <p class="text-xs text-gray-500 mt-0.5">
                                    @foreach($item['modifiers'] as $mod)
                                        {{ $mod['name'] }}@if(!$loop->last), @endif
                                    @endforeach
                                </p>
                            @endif
                            <p class="text-primary-600 font-medium text-sm mt-1">
                                Rp {{ number_format($item['unit_price'] + $item['modifier_total'], 0, ',', '.') }}
                            </p>
                        </div>
                        <button 
                            wire:click="removeFromCart('{{ $cartKey }}')"
                            class="text-gray-400 hover:text-red-500 p-1"
                        >
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    
                    <div 
                        class="flex items-center justify-between mt-3" 
                        x-data="{ 
                            qty: {{ $item['quantity'] }},
                            init() {
                                // Watch local quantity to update server (Debounced)
                                this.$watch('qty', value => {
                                    clearTimeout(this._timer);
                                    this._timer = setTimeout(() => {
                                        // Only update if valid positive number
                                        let val = parseInt(value);
                                        if (val > 0) {
                                            $wire.updateQuantity('{{ $cartKey }}', val);
                                        }
                                    }, 500);
                                });

                                // Watch server quantity to update local
                                this.$watch('$wire.cart[\'{{ $cartKey }}\'].quantity', value => {
                                    if (value !== undefined && value != this.qty) {
                                        this.qty = value;
                                    }
                                });
                            }
                        }"
                    >
                        <div class="flex items-center gap-2">
                            <button 
                                @click="qty > 1 ? qty-- : null"
                                class="w-8 h-8 rounded-lg bg-gray-200 hover:bg-gray-300 flex items-center justify-center active:scale-95 transition-transform"
                                type="button"
                            >
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>
                            <input 
                                type="number" 
                                min="1"
                                x-model="qty"
                                onkeypress="return event.charCode >= 48 && event.charCode <= 57"
                                onpaste="return false"
                                @blur="
                                    if(!qty || qty < 1) { 
                                        qty = $wire.cart['{{ $cartKey }}'].quantity 
                                    } else {
                                        // Sync on Blur: Cancel debounce and force update immediately
                                        clearTimeout(_timer);
                                        $wire.updateQuantity('{{ $cartKey }}', parseInt(qty));
                                    }
                                "
                                class="w-16 text-center font-medium border-0 bg-transparent focus:ring-2 focus:ring-primary-500 rounded-lg p-0"
                            >
                            <button 
                                @click="qty++"
                                class="w-8 h-8 rounded-lg bg-primary-100 hover:bg-primary-200 text-primary-600 flex items-center justify-center active:scale-95 transition-transform"
                                type="button"
                            >
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <span class="font-bold text-gray-800">
                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-400">
                    <i data-lucide="shopping-bag" class="w-16 h-16 mx-auto mb-4"></i>
                    <p>Keranjang kosong</p>
                    <p class="text-sm">Klik produk untuk memulai</p>
                </div>
            @endforelse
        </div>

        <!-- Cart Summary -->
        <div class="border-t p-4 space-y-3 bg-gray-50">
            <div class="flex justify-between text-gray-600">
                <span>Subtotal</span>
                <span>Rp {{ number_format($this->subtotal, 0, ',', '.') }}</span>
            </div>
            @if($this->taxAmount > 0)
                <div class="flex justify-between text-gray-600">
                    <span>Pajak</span>
                    <span>Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between text-xl font-bold text-gray-800 pt-2 border-t">
                <span>Total</span>
                <span class="text-primary-600">Rp {{ number_format($this->total, 0, ',', '.') }}</span>
            </div>

            <button 
                wire:click="openPaymentModal"
                @if(count($cart) === 0) disabled @endif
                class="w-full py-4 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
            >
                <i data-lucide="credit-card" class="w-5 h-5"></i>
                Bayar (F1)
            </button>
        </div>
    </div>

    <!-- Payment Modal -->
    @if($showPaymentModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-6 border-b flex-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="wallet" class="w-6 h-6"></i>
                            Pembayaran
                        </h3>
                        <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600 p-1">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto flex-1 custom-scroll">
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Metode Pembayaran</label>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach($this->paymentSources as $source)
                                <button 
                                    wire:click="$set('paymentSourceId', {{ $source->id }})"
                                    class="p-3 rounded-lg border-2 text-center transition-all {{ $paymentSourceId === $source->id ? 'border-primary-500 bg-primary-50' : 'border-gray-200 hover:border-gray-300' }}"
                                >
                                    <span class="font-medium text-sm">{{ $source->name }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="bg-gray-100 rounded-xl p-4 text-center">
                        <p class="text-gray-600">Total Tagihan</p>
                        <p class="text-3xl font-bold text-primary-600">
                            Rp {{ number_format($this->total, 0, ',', '.') }}
                        </p>
                    </div>

                    <!-- Paid Amount (for cash) -->
                    @php $selectedSource = $this->paymentSources->firstWhere('id', $paymentSourceId); @endphp
                    @if($selectedSource && $selectedSource->type === 'cash')
                        <div x-data="{
                            rawValue: {{ $paidAmount }},
                            formatted: '',
                            init() {
                                this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : '';
                            },
                            formatNumber(num) {
                                return new Intl.NumberFormat('id-ID').format(num || 0);
                            },
                            parseNumber(str) {
                                return parseInt(String(str).replace(/\./g, '').replace(/,/g, '') || 0);
                            },
                            onInput(e) {
                                const input = e.target;
                                const cursorPos = input.selectionStart;
                                const oldLen = this.formatted.length;
                                
                                // Get raw digits only
                                const digits = this.formatted.replace(/\D/g, '');
                                this.rawValue = parseInt(digits) || 0;
                                this.formatted = this.rawValue > 0 ? this.formatNumber(this.rawValue) : '';
                                
                                // Adjust cursor position after formatting
                                const newLen = this.formatted.length;
                                const diff = newLen - oldLen;
                                this.$nextTick(() => {
                                    const newPos = Math.max(0, cursorPos + diff);
                                    input.setSelectionRange(newPos, newPos);
                                });
                            },
                            syncToWire() {
                                $wire.set('paidAmount', this.rawValue);
                            }
                        }">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Bayar</label>
                            <input 
                                type="text"
                                inputmode="numeric"
                                x-model="formatted"
                                @input="onInput($event)"
                                @blur="syncToWire()"
                                @keyup.enter="syncToWire()"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-xl font-bold text-center focus:ring-2 focus:ring-primary-500"
                                placeholder="0"
                            >
                            
                            <!-- Quick amounts -->
                            <div class="grid grid-cols-4 gap-2 mt-3">
                                @foreach([50000, 100000, 150000, 200000] as $amount)
                                    <button 
                                        type="button"
                                        @click="rawValue = {{ $amount }}; formatted = formatNumber({{ $amount }}); $wire.set('paidAmount', {{ $amount }})"
                                        class="py-2 px-3 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium"
                                    >
                                        {{ number_format($amount / 1000) }}rb
                                    </button>
                                @endforeach
                            </div>

                            <!-- Warning if paid < total -->
                            @if($paidAmount > 0 && $paidAmount < $this->total)
                                <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-4 text-center">
                                    <p class="text-red-600 font-medium flex items-center justify-center gap-2">
                                        <i data-lucide="alert-circle" class="w-5 h-5"></i>
                                        Uang kurang Rp {{ number_format($this->total - $paidAmount, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endif

                            @if($paidAmount >= $this->total)
                                <div class="mt-4 bg-green-50 rounded-xl p-4 text-center">
                                    <p class="text-gray-600">Kembalian</p>
                                    <p class="text-2xl font-bold text-green-600">
                                        Rp {{ number_format($this->changeAmount, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Customer Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nama Pelanggan (opsional)</label>
                        <input 
                            type="text" 
                            wire:model="customerName"
                            placeholder="Masukkan nama..."
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500"
                        >
                    </div>
                </div>

                <div class="p-6 border-t bg-gray-50 rounded-b-2xl flex-none">
                    <button 
                        wire:click="processPayment"
                        wire:loading.attr="disabled"
                        class="w-full py-4 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <span wire:loading.remove wire:target="processPayment">
                            <i data-lucide="check-circle" class="w-5 h-5 inline mr-2"></i>
                            Proses Pembayaran (F2)
                        </span>
                        <span wire:loading wire:target="processPayment">
                            Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Close Shift Modal -->
    @if($showCloseShiftModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                            <i data-lucide="clock" class="w-6 h-6"></i>
                            Tutup Shift
                        </h3>
                        <button wire:click="$set('showCloseShiftModal', false)" class="text-gray-400 hover:text-gray-600">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Summary -->
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600">Penjualan Hari Ini</p>
                        <p class="text-2xl font-bold text-blue-700">Rp {{ number_format($this->todayTransactions->sum('total'), 0, ',', '.') }}</p>
                        <p class="text-sm text-blue-600 mt-1">{{ $this->todayTransactions->count() }} transaksi</p>
                    </div>

                    <!-- Opening Cash -->
                    <div x-data="moneyInput({{ $openingCash }}, 'openingCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modal Awal (Cash)</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                    </div>

                    <!-- Expenses -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Pengeluaran</label>
                            <button type="button" wire:click="addExpense" class="text-primary-600 text-sm font-medium flex items-center gap-1">
                                <i data-lucide="plus" class="w-4 h-4"></i> Tambah
                            </button>
                        </div>
                        @foreach($expenses as $index => $expense)
                            <div class="flex gap-2 mb-2" wire:key="expense-{{ $index }}">
                                <input type="text" wire:model="expenses.{{ $index }}.description" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Keterangan">
                                <input type="number" wire:model="expenses.{{ $index }}.amount" class="w-28 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Jumlah">
                                <button wire:click="removeExpense({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <!-- Actual Cash -->
                    <div x-data="moneyInput({{ $actualCash }}, 'actualCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uang Fisik di Laci</label>
                        <input 
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea wire:model="closeNotes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Catatan opsional"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t bg-gray-50 flex gap-3">
                    <button wire:click="$set('showCloseShiftModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Batal</button>
                    <button wire:click="closeShift" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-5 h-5"></i>
                        Tutup & Cetak
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- History Modal -->
    @if($showHistoryModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-5 border-b flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-gray-500"></i>
                        Riwayat Transaksi Hari Ini
                    </h3>
                    <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-0 overflow-y-auto max-h-[60vh]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 font-medium sticky top-0">
                                <tr>
                                    <th class="px-5 py-3 whitespace-nowrap">Waktu</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Invoice</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Pelanggan</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Total</th>
                                    <th class="px-5 py-3 text-center whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($this->todayTransactions->sortByDesc('created_at') as $trx)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 text-gray-500 font-mono whitespace-nowrap">{{ $trx->created_at->format('H:i') }}</td>
                                        <td class="px-5 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $trx->invoice_number }}</td>
                                        <td class="px-5 py-3 text-gray-600 truncate max-w-[150px]">{{ $trx->customer_name ?: '-' }}</td>
                                        <td class="px-5 py-3 font-bold text-gray-800 whitespace-nowrap">Rp {{ number_format($trx->total, 0, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <button wire:click="reprintReceipt({{ $trx->id }})" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Cetak Ulang">
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-gray-500">Belum ada transaksi hari ini</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="p-4 border-t bg-gray-50 text-right">
                    <button wire:click="closeHistoryModal" class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Return Modal -->
    @if($showReturnModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="rotate-ccw" class="w-6 h-6 text-red-600"></i>
                        Buat Retur
                    </h3>
                    <button wire:click="$set('showReturnModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Search Invoice -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cari Invoice</label>
                        <div class="flex gap-2">
                            <input type="text" wire:model="returnInvoiceSearch" placeholder="INV-2025..." class="flex-1 px-4 py-2 border border-gray-200 rounded-lg">
                            <button wire:click="searchReturnInvoice" class="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg">Cari</button>
                        </div>
                    </div>

                    @if($returnTransaction)
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="flex justify-between items-center">
                                <div>
                                    <p class="text-sm text-gray-500">Invoice</p>
                                    <p class="font-medium text-gray-800">{{ $returnTransaction->invoice_number }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Total Awal</p>
                                    <p class="font-medium text-gray-800">Rp {{ number_format($returnTransaction->total, 0, ',', '.') }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Item untuk Diretur</label>
                            <div class="border rounded-lg divide-y">
                                @foreach($returnItems as $detailId => $item)
                                    <div class="p-3 flex items-center gap-4">
                                        <input type="checkbox" wire:model.live="returnItems.{{ $detailId }}.selected" class="w-4 h-4 text-primary-600 border-gray-300 rounded">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-800">{{ $item['product_name'] }}</p>
                                            <p class="text-sm text-gray-500">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input 
                                                type="number" 
                                                wire:model.live="returnItems.{{ $detailId }}.quantity" 
                                                min="1" 
                                                max="{{ $item['max_quantity'] }}"
                                                oninput="if(parseInt(this.value) > {{ $item['max_quantity'] }}) this.value = {{ $item['max_quantity'] }}; if(parseInt(this.value) < 1) this.value = 1;"
                                                class="w-16 px-2 py-1 border border-gray-200 rounded text-center focus:ring-red-500 focus:border-red-500"
                                            >
                                            <span class="text-sm text-gray-500 font-medium">/ {{ $item['max_quantity'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <p class="text-sm text-red-600">Total Refund</p>
                            <p class="text-2xl font-bold text-red-700">Rp {{ number_format($this->calculateReturnTotal(), 0, ',', '.') }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Alasan Retur *</label>
                            <select wire:model="returnReason" class="w-full px-4 py-2 border border-gray-200 rounded-lg">
                                <option value="">Pilih alasan</option>
                                <option value="Produk rusak">Produk rusak</option>
                                <option value="Pesanan salah">Pesanan salah</option>
                                <option value="Pelanggan berubah pikiran">Pelanggan berubah pikiran</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (opsional)</label>
                            <textarea wire:model="returnNotes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg"></textarea>
                        </div>
                    @endif
                </div>
                <div class="p-6 border-t bg-gray-50 flex gap-3">
                    <button wire:click="$set('showReturnModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Batal</button>
                    @if($returnTransaction)
                        <button wire:click="processReturn" class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2">
                            <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                            Proses Retur
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Return History Modal -->
    @if($showReturnHistoryModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                 <div class="p-5 border-b flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5 text-gray-500"></i>
                        Riwayat Retur Hari Ini
                    </h3>
                    <button wire:click="closeReturnHistoryModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
                <div class="p-0 overflow-y-auto max-h-[60vh]">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left">
                            <thead class="bg-gray-50 text-gray-500 font-medium sticky top-0">
                                <tr>
                                    <th class="px-5 py-3 whitespace-nowrap">Waktu</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Retur #</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Invoice Asal</th>
                                    <th class="px-5 py-3 whitespace-nowrap">Total Refund</th>
                                    <th class="px-5 py-3 text-center whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($this->todayReturns as $retur)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-5 py-3 text-gray-500 font-mono whitespace-nowrap">{{ $retur->created_at->format('H:i') }}</td>
                                        <td class="px-5 py-3 font-medium text-gray-800 whitespace-nowrap">{{ $retur->return_number }}</td>
                                        <td class="px-5 py-3 text-gray-600 truncate">{{ $retur->transaction?->invoice_number }}</td>
                                        <td class="px-5 py-3 font-bold text-red-600 whitespace-nowrap">Rp {{ number_format($retur->total_refund, 0, ',', '.') }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <button wire:click="printReturnReceipt({{ $retur->id }})" class="p-2 text-gray-500 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Cetak Retur">
                                                <i data-lucide="printer" class="w-4 h-4"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-5 py-8 text-center text-gray-500">Belum ada retur hari ini</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                 <div class="p-4 border-t bg-gray-50 text-right">
                    <button wire:click="closeReturnHistoryModal" class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Receipt Modal -->
    @if($showReceiptModal && $lastTransaction)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4" style="z-index: 9999;">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-4 border-b flex justify-between items-center flex-none">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                        Struk Transaksi
                    </h3>
                    <button wire:click="closeReceiptModal" class="text-gray-400 hover:text-gray-600 p-1" type="button">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-container" class="p-4 overflow-y-auto flex-1 custom-scroll">
                    @include('livewire.partials.receipt', ['transaction' => $lastTransaction])
                </div>

                <div class="p-4 border-t flex gap-3 flex-none">
                    <button 
                        wire:click="printReceipt"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                        type="button"
                    >
                        <i data-lucide="printer" class="w-5 h-5"></i>
                        Cetak Ulang
                    </button>
                    <button 
                        wire:click="closeReceiptModal"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
                        type="button"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

@script
<script>
    // Initialize Lucide icons
    lucide.createIcons();
    Livewire.hook('morph.updated', () => {
        setTimeout(() => lucide.createIcons(), 50);
    });

    // Print receipt event
    $wire.on('print-receipt', () => {
        setTimeout(() => {
            window.print();
        }, 300);
    });

    // Print shift receipt (Legacy, kept just in case)
    $wire.on('print-shift-receipt', () => {
        setTimeout(() => {
            window.print();
        }, 300);
    });

    // Open new window (For Shift Detail & Returns)
    $wire.on('open-new-window', (data) => {
        const url = data.url;
        if (url) {
             window.open(url, '_blank');
        }
    });
</script>
@endscript
