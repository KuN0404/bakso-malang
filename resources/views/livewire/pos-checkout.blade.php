<div class="fixed inset-0 w-full flex bg-gray-100 overflow-hidden" style="height: 100dvh;"
    x-data="{ showProductModal: false, selectedProduct: null, showCartMobile: false }"
    @keydown.f1.window.prevent="$wire.openPaymentModal()"
    @keydown.f2.window.prevent="$wire.processPayment()"
    @keydown.f3.window.prevent="if(confirm('Hapus semua item?')) $wire.clearCart()"
    @keydown.f4.window.prevent="$wire.openCloseShiftModal()"
    @keydown.escape.window="$wire.closePaymentModal(); $wire.closeReceiptModal(); $wire.set('showCloseShiftModal', false)">
    
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
                    <button wire:click="openHistoryModal" class="flex items-center gap-2 px-3 py-2 bg-gray-50 hover:bg-gray-100 rounded-lg text-gray-700 transition-colors border border-gray-200">
                        <i data-lucide="history" class="w-4 h-4 text-gray-500"></i>
                        <span class="text-sm font-medium hidden md:inline">Riwayat</span>
                        <span class="bg-green-100 text-green-700 text-xs py-0.5 px-2 rounded-full font-bold ml-1">
                            {{ $this->todayTransactions->count() }}
                        </span>
                    </button>
                    
                    <button wire:click="openReturnModal" class="flex items-center gap-2 px-3 py-2 bg-red-50 hover:bg-red-100 rounded-lg text-red-600 transition-colors border border-red-200">
                        <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                        <span class="text-sm font-medium hidden md:inline">Retur</span>
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
                    <div 
                        wire:click="addToCart({{ $product->id }}, [])"
                        wire:key="product-{{ $product->id }}"
                        class="bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group border border-gray-100 hover:border-primary-300"
                    >
                        @if($product->image)
                            <img src="{{ asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="w-full h-24 object-cover rounded-lg mb-3">
                        @else
                            <div class="w-full h-24 bg-gradient-to-br from-primary-100 to-primary-200 rounded-lg mb-3 flex items-center justify-center">
                                <i data-lucide="package" class="w-8 h-8 text-primary-500"></i>
                            </div>
                        @endif
                        <h3 class="font-medium text-gray-800 group-hover:text-primary-600 transition-colors line-clamp-2">
                            {{ $product->name }}
                        </h3>
                        <p class="text-primary-600 font-bold mt-1">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                        @if($product->track_stock)
                            <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
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
                    
                    <div class="flex items-center justify-between mt-3">
                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] - 1 }})"
                                class="w-8 h-8 rounded-lg bg-gray-200 hover:bg-gray-300 flex items-center justify-center"
                            >
                                <i data-lucide="minus" class="w-4 h-4"></i>
                            </button>
                            <span class="w-8 text-center font-medium">{{ $item['quantity'] }}</span>
                            <button 
                                wire:click="updateQuantity('{{ $cartKey }}', {{ $item['quantity'] + 1 }})"
                                class="w-8 h-8 rounded-lg bg-primary-100 hover:bg-primary-200 text-primary-600 flex items-center justify-center"
                            >
                                <i data-lucide="plus" class="w-4 h-4"></i>
                            </button>
                        </div>
                        <span class="font-bold text-gray-800">
                            Rp {{ number_format($item['subtotal'] * $item['quantity'], 0, ',', '.') }}
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
            <div class="bg-white rounded-2xl w-full max-w-lg mx-4 shadow-2xl">
                <div class="p-6 border-b">
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

                <div class="p-6 space-y-6">
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
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Jumlah Bayar</label>
                            <input 
                                type="number" 
                                wire:model.live="paidAmount"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-xl font-bold text-center focus:ring-2 focus:ring-primary-500"
                            >
                            
                            <!-- Quick amounts -->
                            <div class="grid grid-cols-4 gap-2 mt-3">
                                @foreach([50000, 100000, 150000, 200000] as $amount)
                                    <button 
                                        wire:click="setPaidAmount({{ $amount }})"
                                        class="py-2 px-3 bg-gray-100 hover:bg-gray-200 rounded-lg text-sm font-medium"
                                    >
                                        {{ number_format($amount / 1000) }}rb
                                    </button>
                                @endforeach
                            </div>

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

                <div class="p-6 border-t bg-gray-50 rounded-b-2xl">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modal Awal (Cash)</label>
                        <input type="number" wire:model="openingCash" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg" placeholder="0">
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
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Uang Fisik di Laci</label>
                        <input type="number" wire:model="actualCash" class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg" placeholder="0">
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
                                            <input type="number" wire:model.live="returnItems.{{ $detailId }}.quantity" min="1" max="{{ $item['max_quantity'] }}" class="w-16 px-2 py-1 border border-gray-200 rounded text-center">
                                            <span class="text-sm text-gray-500">/ {{ $item['max_quantity'] }}</span>
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

    <!-- Receipt Modal -->
    @if($showReceiptModal && $lastTransaction)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
            <div class="bg-white rounded-2xl w-full max-w-md mx-4 shadow-2xl">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <i data-lucide="receipt" class="w-5 h-5"></i>
                        Struk Transaksi
                    </h3>
                    <button wire:click="closeReceiptModal" class="text-gray-400 hover:text-gray-600 p-1">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-container" class="p-4">
                    @include('livewire.partials.receipt', ['transaction' => $lastTransaction])
                </div>

                <div class="p-4 border-t flex gap-3">
                    <button 
                        wire:click="printReceipt"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                    >
                        <i data-lucide="printer" class="w-5 h-5"></i>
                        Cetak Ulang
                    </button>
                    <button 
                        wire:click="closeReceiptModal"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
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

    // Print shift receipt
    $wire.on('print-shift-receipt', () => {
        setTimeout(() => {
            window.print();
        }, 300);
    });
</script>
@endscript
