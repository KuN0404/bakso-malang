<div class="h-screen bg-gray-50 flex flex-col overflow-hidden relative" 
     x-data="{
         cart: @js($cart),
         subtotal: @js($subtotal),
         taxAmount: @js($taxAmount),
         total: @js($total),
         paidAmount: @js($paidAmount),
         changeAmount: @js($changeAmount),
         customerName: @js($customerName),
         cashierName: @js($cashierName),
         showMenuModal: false,
         selectedCategory: null,
         searchQuery: '',
         detailProduct: null,
         qrisOrderId: @js($qrisOrderId),
         qrisCodeUrl: @js($qrisCodeUrl),
         qrisExpiresIn: @js($qrisExpiresIn),
         qrisExpiresAtMs: @js($qrisExpiresAtMs),
         showQris: @js($showQris),
         lastUpdatedAt: @js($lastUpdatedAt),
         qrisCountdown: @js($qrisExpiresIn),
         isCheckingQris: false,
         qrisStatusText: '',
         qrisTimer: null,
         // ID order QRIS yang sudah pernah kita nyatakan kadaluarsa DI SISI CLIENT. Halaman ini
         // menerima banyak sumber update yang tumpang tindih (broadcast langsung, $refresh()
         // async, dan wire:poll di halaman kasir yang otomatis memicu re-sync tiap beberapa
         // detik walau kasir tidak klik apapun). Tanpa penanda ini, salah satu sumber yang
         // membawa qris_expires_at_ms lama bisa memicu initQrisTimer() lagi setelah countdown
         // sempat mencapai 00:00, sehingga angkanya lompat balik ke durasi awal (mis. 4:59)
         // lalu diam karena tick() berikutnya langsung menganggapnya sudah kadaluarsa lagi.
         qrisExpiredOrderId: null,
         initQrisTimer() {
             if (this.qrisTimer) clearInterval(this.qrisTimer);
             if (!this.showQris || !this.qrisExpiresAtMs) return;
             if (this.qrisOrderId && this.qrisOrderId === this.qrisExpiredOrderId) {
                 // Order QRIS yang sama sudah dinyatakan kadaluarsa sebelumnya — kunci di 00:00,
                 // jangan hitung ulang dari qrisExpiresAtMs walau data itu datang lagi.
                 this.qrisCountdown = 0;
                 return;
             }
             const tick = () => {
                 this.qrisCountdown = Math.max(0, Math.round((this.qrisExpiresAtMs - Date.now()) / 1000));
                 if (this.qrisCountdown <= 0) {
                     this.qrisExpiredOrderId = this.qrisOrderId;
                     clearInterval(this.qrisTimer);
                 }
             };
             tick();
             this.qrisTimer = setInterval(() => tick(), 1000);
         },
         formatCountdown(seconds) {
             if (!seconds || seconds <= 0) return '00:00';
             const m = Math.floor(seconds / 60).toString().padStart(2, '0');
             const s = (seconds % 60).toString().padStart(2, '0');
             return `${m}:${s}`;
         },
         async checkQrisStatus() {
             if (!this.qrisOrderId || this.isCheckingQris) return;
             this.isCheckingQris = true;
             this.qrisStatusText = 'Mengecek status pembayaran...';
             try {
                 const res = await fetch(`/api/payment/qris-check/${this.qrisOrderId}`);
                 const data = await res.json();
                 if (data.status === 'settlement' || data.status === 'capture' || data.status === 'paid') {
                     this.qrisStatusText = 'Pembayaran Berhasil!';

                     // Kirim sinyal langsung ke layar POS kasir via BroadcastChannel
                     try {
                         const posChannel = new BroadcastChannel('pos_channel_' + {{ auth()->id() }});
                         posChannel.postMessage({
                             type: 'qris_paid',
                             transaction_id: data.transaction_id,
                             order_id: this.qrisOrderId
                         });
                     } catch (e) { console.log(e); }

                     setTimeout(() => {
                         this.showQris = false;
                         this.clearDisplay();
                         this.qrisStatusText = '';
                     }, 1500);
                 } else if (data.status === 'expire' || data.status === 'expired') {
                     this.qrisStatusText = 'QRIS telah kadaluarsa.';
                 } else {
                     this.qrisStatusText = 'Pembayaran belum diterima. Silakan selesaikan pembayaran.';
                 }
             } catch (err) {
                 this.qrisStatusText = 'Gagal mengecek status.';
             } finally {
                 this.isCheckingQris = false;
             }
         },
         formatNumber(num) {
             return new Intl.NumberFormat('id-ID').format(num || 0);
         },
         clearDisplay() {
             this.cart = [];
             this.subtotal = 0;
             this.taxAmount = 0;
             this.total = 0;
             this.paidAmount = 0;
             this.changeAmount = 0;
             this.customerName = '';
             this.cashierName = '';
             this.qrisOrderId = null;
             this.qrisCodeUrl = null;
             this.showQris = false;
         }
     }"
     x-init="initQrisTimer()"
     @cart-data-broadcast.window="
         if (($event.detail.updated_at || 0) < lastUpdatedAt) {
             // Pesan ini lebih lama dari state yang sudah diterapkan (bisa datang belakangan
             // karena race antar-pesan BroadcastChannel, mis. saat QRIS baru dibuat) — abaikan
             // agar tidak menimpa data yang lebih baru (mis. gambar QRIS jadi hilang lagi).
             return;
         }
         lastUpdatedAt = $event.detail.updated_at || lastUpdatedAt;
         cart = $event.detail.cart || [];
         subtotal = $event.detail.subtotal || 0;
         taxAmount = $event.detail.tax_amount || 0;
         total = $event.detail.total || 0;
         paidAmount = $event.detail.paid_amount || 0;
         changeAmount = $event.detail.change_amount || 0;
         customerName = $event.detail.customer_name || '';
         cashierName = $event.detail.cashier_name || '';
         qrisOrderId = $event.detail.qris_order_id || null;
         qrisCodeUrl = $event.detail.qris_code_url || null;
         qrisExpiresIn = $event.detail.qris_expires_in || 0;
         qrisExpiresAtMs = $event.detail.qris_expires_at_ms || null;
         showQris = !!$event.detail.show_qris;
         qrisStatusText = '';
         if (showQris && qrisExpiresAtMs) {
             initQrisTimer();
         }
         $nextTick(() => {
             const container = document.getElementById('items-container');
             if (container) {
                 container.scrollTo({
                     top: container.scrollHeight,
                     behavior: 'smooth'
                 });
             }
         });
     "
     @keydown.escape.window="showMenuModal = false; detailProduct = null"
>
    <!-- Header -->
    <div class="bg-white shadow-sm border-b px-6 py-4 flex justify-between items-center transition-all duration-300 z-10 flex-shrink-0">
        <div class="flex items-center gap-3">
            <div class="bg-primary-600 p-2.5 rounded-xl shadow-md shadow-primary-500/20">
                <x-lucide name="monitor" class="w-6 h-6 text-white" />
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-gray-800 tracking-tight">Layar Pelanggan</h1>
                <p class="text-xs text-gray-500 font-medium" x-show="cashierName" x-text="'Kasir: ' + cashierName"></p>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <span class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-2 rounded-full font-bold text-base shadow-2xs" 
                  x-show="customerName" 
                  x-text="'Pelanggan: ' + customerName">
            </span>

            <!-- Menu Toggle Button for Customer Display -->
            <button 
                @click="showMenuModal = !showMenuModal"
                class="flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-primary-600 to-indigo-600 hover:from-primary-700 hover:to-indigo-700 text-white font-bold rounded-xl shadow-md shadow-primary-500/25 transition-all transform hover:scale-[1.02] active:scale-95 cursor-pointer"
            >
                <x-lucide name="utensils" class="w-5 h-5" />
                <span>Katalog Menu</span>
            </button>
        </div>
    </div>

    <!-- Content -->
    <!-- EMPTY STATE: Full Screen -->
    <div x-show="Object.keys(cart).length === 0" class="flex-1 flex flex-col items-center justify-center bg-white text-center p-8 animate-fade-in overflow-hidden">
        <div class="w-56 h-56 bg-primary-50/60 rounded-full flex items-center justify-center mb-6 shadow-inner border border-primary-100/50">
            <x-lucide name="shopping-bag" class="w-28 h-28 text-primary-400" />
        </div>
        <h2 class="text-4xl font-extrabold text-gray-800 mb-3 tracking-tight">Selamat Datang</h2>
        <p class="text-xl text-gray-500 font-light max-w-md">Silakan lihat menu favorit kami dan pilih sesuai selera Anda</p>
        
        <button 
            @click="showMenuModal = true"
            class="mt-8 px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white font-extrabold text-lg rounded-2xl shadow-xl shadow-primary-500/30 transition-all transform hover:scale-105 flex items-center gap-3 cursor-pointer"
        >
            <x-lucide name="utensils" class="w-6 h-6" />
            <span>Lihat Katalog Menu Berfoto</span>
        </button>

        <div class="mt-10 flex gap-2">
            <div class="w-2.5 h-2.5 rounded-full bg-primary-300 animate-bounce"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-primary-400 animate-bounce delay-100"></div>
            <div class="w-2.5 h-2.5 rounded-full bg-primary-500 animate-bounce delay-200"></div>
        </div>
    </div>

    <!-- ACTIVE STATE: Split View -->
    <div x-show="Object.keys(cart).length > 0" class="flex-1 flex flex-col md:flex-row overflow-hidden" style="display: none;">
        <!-- LEFT: Items List (60% on desktop) -->
        <div id="items-container" class="w-full md:w-3/5 p-4 overflow-y-auto border-b md:border-b-0 md:border-r border-gray-200 bg-white custom-scroll scroll-smooth relative">
            <div class="space-y-3 pb-4">
                <template x-for="(item, cartKey) in cart" :key="cartKey">
                    <div class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-100 bg-gray-50/80 hover:bg-white hover:shadow-md transition-all group">
                        <!-- Quantity Badge -->
                        <div class="flex-shrink-0 w-10 h-10 bg-primary-600 text-white rounded-lg flex items-center justify-center font-extrabold text-base shadow-sm"
                             x-text="item.quantity + 'x'">
                        </div>
                        
                        <!-- Detail -->
                        <div class="flex-1 min-w-0">
                            <h3 class="text-base font-bold text-gray-800 leading-tight truncate" x-text="item.product_name"></h3>
                            <div class="text-xs text-gray-500 font-semibold mt-0.5" 
                                 x-text="'@ Rp ' + formatNumber(parseFloat(item.unit_price) + parseFloat(item.modifier_total || 0))">
                            </div>
                            <div class="mt-1.5 flex flex-wrap gap-1" x-show="item.modifiers && Object.keys(item.modifiers).length > 0">
                                <template x-for="(mod, modKey) in item.modifiers" :key="modKey">
                                    <span class="text-[11px] bg-white border border-gray-200 px-2 py-0.5 rounded-md text-gray-600 flex items-center gap-1">
                                        <span x-text="'+ ' + mod.name"></span>
                                        <span class="font-bold text-gray-500" 
                                              x-show="mod.price > 0" 
                                              x-text="'(Rp ' + formatNumber(mod.price) + ')'">
                                        </span>
                                    </span>
                                </template>
                            </div>
                        </div>
                        
                        <!-- Subtotal -->
                        <div class="text-right">
                            <span class="text-lg font-extrabold text-gray-800" x-text="'Rp ' + formatNumber(item.subtotal)"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- RIGHT: Allocations & Totals (40% on desktop) -->
        <div class="w-full md:w-2/5 bg-gray-50 flex flex-col shadow-inner overflow-hidden">
            <!-- Totals Section -->
            <div class="flex-1 p-8 flex flex-col justify-center space-y-6 overflow-y-auto">
                <div class="space-y-3 border-b border-gray-200 pb-6">
                    <div class="flex justify-between items-center text-gray-600 text-lg">
                        <span>Subtotal</span>
                        <span class="font-semibold" x-text="'Rp ' + formatNumber(subtotal)"></span>
                    </div>
                    <div class="flex justify-between items-center text-gray-600 text-lg" x-show="taxAmount > 0">
                        <span>Pajak</span>
                        <span class="font-semibold" x-text="'Rp ' + formatNumber(taxAmount)"></span>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <span class="text-2xl font-bold text-gray-800">Total Tagihan</span>
                    <span class="text-5xl font-black text-primary-600 tracking-tight" x-text="'Rp ' + formatNumber(total)"></span>
                </div>
            </div>

            <!-- Payment Status (Bottom) -->
            <div class="bg-white p-8 border-t border-gray-200 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)] flex-shrink-0">
                <div x-show="paidAmount > 0" class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-xl font-medium text-gray-600">Uang Diterima</span>
                        <span class="text-xl font-bold text-gray-800" x-text="'Rp ' + formatNumber(paidAmount)"></span>
                    </div>
                    
                    <div class="flex justify-between items-center p-4 rounded-xl text-green-800 bg-green-100 shadow-inner">
                        <span class="text-2xl font-bold uppercase">Kembalian</span>
                        <span class="text-4xl font-extrabold" x-text="'Rp ' + formatNumber(Math.max(0, changeAmount))"></span>
                    </div>
                </div>
                <div x-show="!paidAmount || paidAmount <= 0" class="text-center text-gray-400 py-4 flex flex-col items-center gap-2">
                     <div class="animate-pulse">
                        <x-lucide name="credit-card" class="w-8 h-8 opacity-50" />
                     </div>
                    <p class="text-lg font-bold">Menunggu Pembayaran...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOMER QRIS PAYMENT MODAL OVERLAY -->
    <div
        x-show="showQris && qrisCodeUrl"
        x-cloak
        class="fixed inset-0 z-40 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-md"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-md overflow-hidden">
            <!-- Header -->
            <div class="bg-primary-600 p-5 text-white text-center">
                <h2 class="text-lg font-bold">Pembayaran QRIS</h2>
                <p class="text-xs text-white/70 mt-0.5">Scan dengan aplikasi e-Wallet atau m-Banking</p>
            </div>

            <!-- Body -->
            <div class="p-6 text-center space-y-5">
                <!-- Total Tagihan -->
                <div>
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide block">Total Pembayaran</span>
                    <span class="text-3xl font-extrabold text-gray-900" x-text="'Rp ' + formatNumber(total)"></span>
                </div>

                <!-- QR Code Box -->
                <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm mx-auto w-fit">
                    <template x-if="qrisCodeUrl">
                        <img :src="qrisCodeUrl" alt="Kode QRIS Pembayaran" class="w-56 h-56 mx-auto object-contain rounded-lg">
                    </template>
                </div>

                <!-- Live Status -->
                <div x-show="qrisCountdown > 0 && !qrisStatusText" class="inline-flex items-center gap-1.5 bg-blue-50 text-primary-600 text-xs font-semibold px-3 py-1.5 rounded-full">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary-600"></span>
                    <span>Menunggu scan &amp; pembayaran</span>
                </div>

                <!-- Timer & Feedback -->
                <div class="space-y-1.5">
                    <div class="text-xs text-gray-500">
                        Berlaku hingga
                        <span class="font-mono font-bold text-gray-900 text-sm" x-text="formatCountdown(qrisCountdown)"></span>
                    </div>
                    <!-- Hanya salah satu pesan status yang tampil sekaligus (kadaluarsa statis ATAU
                         hasil pengecekan), supaya tidak muncul dua badge status yang tumpang tindih. -->
                    <div x-show="qrisCountdown <= 0 && !qrisStatusText" class="text-xs font-semibold text-red-500">
                        Kode QR kadaluarsa. Silakan minta kasir membuat QRIS baru.
                    </div>
                    <div x-show="qrisStatusText" class="text-xs font-semibold text-primary-600 bg-blue-50 p-2.5 rounded-xl mt-2" x-text="qrisStatusText">
                    </div>
                </div>

                <!-- Tombol HANYA Cek Status Pembayaran (Tanpa Tombol Batal) -->
                <div class="pt-2">
                    <button
                        @click="checkQrisStatus()"
                        :disabled="isCheckingQris"
                        class="w-full py-3.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold rounded-xl text-sm flex items-center justify-center gap-2 transition-all cursor-pointer"
                    >
                        <template x-if="!isCheckingQris">
                            <span class="flex items-center gap-2">
                                <x-lucide name="refresh-cw" class="w-4.5 h-4.5" />
                                <span>Cek Status Pembayaran</span>
                            </span>
                        </template>
                        <template x-if="isCheckingQris">
                            <span class="flex items-center gap-2">
                                <div class="animate-spin w-4.5 h-4.5 border-2 border-white border-t-transparent rounded-full"></div>
                                <span>Mengecek Status...</span>
                            </span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- CUSTOMER DISPLAY MENU MODAL DRAWER (FULL SCREEN SLIDE-OVER) -->
    <div 
        x-show="showMenuModal" 
        x-cloak 
        class="fixed inset-0 z-50 overflow-hidden flex justify-end"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    >
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showMenuModal = false"></div>

        <!-- Slide-over Container -->
        <div 
            class="relative w-full max-w-4xl bg-gray-50 h-full shadow-2xl flex flex-col z-10 transform transition-transform duration-300"
            x-show="showMenuModal"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="translate-x-full"
        >
            <!-- Drawer Header -->
            <div class="bg-white px-6 py-4 border-b flex justify-between items-center flex-shrink-0 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-primary-100 text-primary-700 rounded-xl">
                        <x-lucide name="utensils" class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight">Katalog Menu & Harga</h2>
                        <p class="text-xs text-gray-500">Pilih menu favorit Anda</p>
                    </div>
                </div>

                <!-- Search & Close -->
                <div class="flex items-center gap-4">
                    <div class="relative w-64 hidden sm:block">
                        <input 
                            type="text" 
                            x-model="searchQuery" 
                            placeholder="Cari produk..." 
                            class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-primary-500 focus:outline-none"
                        >
                        <x-lucide name="search" class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    </div>

                    <button 
                        @click="showMenuModal = false"
                        class="p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-full transition-colors"
                        title="Tutup"
                    >
                        <x-lucide name="x" class="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- Category Pills Bar -->
            <div class="bg-white px-6 py-3 border-b flex items-center gap-2 overflow-x-auto custom-scroll shrink-0">
                <button 
                    @click="selectedCategory = null"
                    :class="selectedCategory === null ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                    class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors"
                >
                    Semua Kategori
                </button>

                @foreach($categories as $cat)
                    <button 
                        @click="selectedCategory = {{ $cat->id }}"
                        :class="selectedCategory === {{ $cat->id }} ? 'bg-primary-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                        class="px-4 py-2 rounded-xl text-xs font-bold whitespace-nowrap transition-colors"
                    >
                        {{ $cat->name }} ({{ $cat->products_count }})
                    </button>
                @endforeach
            </div>

            <!-- Drawer Products Grid -->
            <div class="p-6 flex-1 overflow-y-auto custom-scroll">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    @foreach($products as $product)
                        <div 
                            x-show="(selectedCategory === null || selectedCategory === {{ $product->category_id }}) && ('{{ addslashes(strtolower($product->name)) }}'.includes(searchQuery.toLowerCase()))"
                            @click="detailProduct = @js($product)"
                            class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden cursor-pointer flex flex-col group"
                        >
                            <!-- Image -->
                            <div class="relative h-44 w-full bg-gray-100 overflow-hidden">
                                @if($product->image)
                                    <img src="{{ asset('storage/' . $product->image) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                @else
                                    <div class="w-full h-full flex flex-col items-center justify-center bg-gray-50 text-gray-300">
                                        <x-lucide name="soup" class="w-12 h-12 stroke-[1.5]" />
                                    </div>
                                @endif

                                <div class="absolute top-2.5 left-2.5">
                                    <span class="bg-black/60 backdrop-blur-md text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full">
                                        {{ $product->category->name ?? 'Umum' }}
                                    </span>
                                </div>

                                @if(!$product->isAvailable())
                                    <div class="absolute inset-0 bg-black/50 backdrop-blur-[1px] flex items-center justify-center">
                                        <span class="bg-red-600 text-white text-xs font-bold px-3 py-1 rounded-full uppercase">Habis</span>
                                    </div>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="p-4 flex-1 flex flex-col justify-between space-y-2">
                                <div>
                                    <h3 class="font-bold text-gray-800 text-base leading-snug line-clamp-1 group-hover:text-primary-600 transition-colors">
                                        {{ $product->name }}
                                    </h3>
                                    @if($product->description)
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $product->description }}</p>
                                    @endif
                                </div>

                                <div class="pt-2 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-lg font-extrabold text-primary-600">
                                        Rp {{ number_format($product->price, 0, ',', '.') }}
                                    </span>
                                    <button class="p-2 bg-primary-50 text-primary-600 rounded-lg group-hover:bg-primary-600 group-hover:text-white transition-colors">
                                        <x-lucide name="eye" class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Product Zoom Detail Modal -->
    <div 
        x-show="detailProduct !== null" 
        x-cloak 
        class="fixed inset-0 z-[60] flex items-center justify-center p-4"
    >
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm" @click="detailProduct = null"></div>

        <div class="bg-white rounded-3xl shadow-2xl max-w-md w-full z-10 overflow-hidden relative flex flex-col max-h-[85vh] animate-fade-in">
            <button @click="detailProduct = null" class="absolute top-3 right-3 z-20 p-2 bg-black/50 hover:bg-black/70 text-white rounded-full transition-colors">
                <x-lucide name="x" class="w-5 h-5" />
            </button>

            <div class="h-60 w-full bg-gray-100 relative shrink-0">
                <template x-if="detailProduct && detailProduct.image">
                    <img :src="'/storage/' + detailProduct.image" class="w-full h-full object-cover">
                </template>
                <template x-if="!detailProduct || !detailProduct.image">
                    <div class="w-full h-full flex items-center justify-center bg-gray-100 text-gray-300">
                        <x-lucide name="soup" class="w-20 h-20" />
                    </div>
                </template>
                <div class="absolute bottom-3 left-3" x-show="detailProduct && detailProduct.category">
                    <span class="bg-primary-600 text-white text-xs font-bold px-3 py-1 rounded-full" x-text="detailProduct?.category?.name"></span>
                </div>
            </div>

            <div class="p-6 overflow-y-auto space-y-4 flex-1">
                <div class="flex justify-between items-start gap-4">
                    <h2 class="text-2xl font-bold text-gray-800 leading-tight" x-text="detailProduct?.name"></h2>
                    <span class="text-2xl font-extrabold text-primary-600 shrink-0" x-text="'Rp ' + formatNumber(detailProduct?.price)"></span>
                </div>

                <div x-show="detailProduct?.description" class="bg-gray-50 p-4 rounded-2xl border border-gray-100">
                    <p class="text-sm text-gray-600 leading-relaxed" x-text="detailProduct?.description"></p>
                </div>
            </div>

            <div class="p-4 border-t bg-gray-50 flex justify-end">
                <button @click="detailProduct = null" class="px-6 py-2.5 bg-gray-200 hover:bg-gray-300 text-gray-800 text-sm font-bold rounded-xl transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
             const channel = new BroadcastChannel('pos_channel_' + {{ auth()->id() }});

             const syncFromServer = () => {
                 @this.$refresh().then(() => {
                     const root = document.querySelector('[x-data]');
                     if (root && root.__x) {
                         // Sama seperti guard di handler cart-data-broadcast: request $refresh()
                         // ini async (roundtrip network), jadi bisa saja RESOLVE BELAKANGAN
                         // setelah pesan BroadcastChannel yang lebih baru sudah diterapkan
                         // (mis. QRIS baru dibuat tepat setelah $refresh() ini dikirim). Tanpa
                         // guard ini, hasil fetch yang basi bisa menimpa gambar QRIS yang baru
                         // saja tampil sehingga terlihat "hilang" sampai browser di-refresh manual.
                         if ((@this.lastUpdatedAt || 0) < root.__x.$data.lastUpdatedAt) {
                             return;
                         }
                         root.__x.$data.lastUpdatedAt = @this.lastUpdatedAt;
                         root.__x.$data.cart = @this.cart;
                         root.__x.$data.subtotal = @this.subtotal;
                         root.__x.$data.taxAmount = @this.taxAmount;
                         root.__x.$data.total = @this.total;
                         root.__x.$data.paidAmount = @this.paidAmount;
                         root.__x.$data.changeAmount = @this.changeAmount;
                         root.__x.$data.customerName = @this.customerName;
                         root.__x.$data.cashierName = @this.cashierName;
                         root.__x.$data.qrisOrderId = @this.qrisOrderId;
                         root.__x.$data.qrisCodeUrl = @this.qrisCodeUrl;
                         root.__x.$data.qrisExpiresIn = @this.qrisExpiresIn;
                         root.__x.$data.qrisExpiresAtMs = @this.qrisExpiresAtMs;
                         root.__x.$data.showQris = @this.showQris;
                         if (@this.showQris && @this.qrisExpiresAtMs) {
                             root.__x.$data.initQrisTimer();
                         }
                     }
                 });
             };

             // Tarik state terkini SEKALI saat display baru terbuka/reload. Ini menutup celah
             // race condition di mana kasir sudah membuat QRIS SEBELUM layar display ini selesai
             // memuat & mendaftarkan listener BroadcastChannel-nya — pesan broadcast pertama
             // (mis. gambar QR) bisa terlewat karena channel di sisi display belum "ada" saat
             // pesan itu dikirim. Bukan polling berkala, hanya sinkronisasi satu kali di awal.
             syncFromServer();

             channel.onmessage = (e) => {
                  if (e.data && e.data.type === 'heartbeat') return;
                  
                  if (e.data && typeof e.data === 'object' && e.data.cart) {
                      window.dispatchEvent(new CustomEvent('cart-data-broadcast', { detail: e.data }));
                  } else {
                      syncFromServer();
                  }
             };

             // Jaring pengaman tambahan: kalau display sempat di-minimize/background lalu
             // kembali aktif (visibilitychange) atau window di-focus lagi, tarik state terkini
             // sekali — bukan interval berkala, hanya re-sync saat display benar-benar kembali dilihat.
             document.addEventListener('visibilitychange', () => {
                 if (document.visibilityState === 'visible') syncFromServer();
             });
             window.addEventListener('focus', syncFromServer);

             // Self-heal berkala: BroadcastChannel seharusnya real-time, tapi tetap bisa ada
             // pesan yang terlewat (mis. sinyal "tutup/sembunyikan QRIS" yang bersamaan dengan
             // pesan lain di channel yang sama) sehingga display kadang nyangkut di state lama
             // walau kasir sudah menutup/mengubahnya. Poll ringan tiap 5 detik ini menjamin
             // display otomatis kembali sinkron paling lambat dalam beberapa detik, terlepas
             // dari race di jalur broadcast manapun. Guard lastUpdatedAt di syncFromServer()
             // tetap mencegah hasil fetch yang basi menimpa data yang lebih baru.
             setInterval(syncFromServer, 5000);
        });
    </script>
    
    <style>
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        .custom-scroll::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scroll::-webkit-scrollbar-track { background: transparent; }
        .custom-scroll::-webkit-scrollbar-thumb { background-color: #e5e7eb; border-radius: 20px; }
    </style>
</div>
