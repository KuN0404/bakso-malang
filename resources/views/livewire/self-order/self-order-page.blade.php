<div class="flex flex-col min-h-screen bg-gray-50">

    {{-- ─── Header ─────────────────────────────────────────────── --}}
    <header class="bg-brand text-white sticky top-0 z-50 shadow-md">
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4m1.6 8L5 3H3m4 10v6a1 1 0 001 1h8a1 1 0 001-1v-6m-9 0h8"/>
                </svg>
                <span class="font-bold text-lg leading-tight">{{ $this->storeName }}</span>
            </div>

            {{-- Cart Badge --}}
            @if($step === 'menu')
            <button wire:click="goToCheckout"
                class="relative flex items-center gap-1.5 bg-white/20 hover:bg-white/30 transition rounded-lg px-3 py-1.5 text-sm font-semibold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                @if($this->cartCount > 0)
                    <span class="absolute -top-1.5 -right-1.5 bg-amber-400 text-gray-900 text-xs font-bold rounded-md px-1.5 py-0.5 min-w-[20px] text-center shadow">
                        {{ $this->cartCount }}
                    </span>
                @endif
                <span>Keranjang</span>
            </button>
            @endif

            @if($step === 'checkout')
            <button wire:click="backToMenu" class="flex items-center gap-1 text-white/80 hover:text-white transition text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Kembali
            </button>
            @endif
        </div>

        {{-- Step Indicator --}}
        <div class="px-4 pb-2 flex gap-2">
            <span class="text-xs {{ $step === 'menu' ? 'text-white font-semibold' : 'text-white/50' }}">
                1. Pilih Menu
            </span>
            <span class="text-white/30 text-xs">›</span>
            <span class="text-xs {{ $step === 'checkout' ? 'text-white font-semibold' : 'text-white/50' }}">
                2. Checkout
            </span>
        </div>
    </header>

    {{-- ─── Flash Message ───────────────────────────────────────── --}}
    @if($flashMessage)
    <div x-data="{ show: true }"
         x-init="setTimeout(() => { show = false; $wire.clearFlash(); }, 2000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
         class="fixed top-16 left-1/2 -translate-x-1/2 z-50 max-w-xs w-full px-4"
         wire:key="flash-toast-{{ md5($flashMessage) }}">
        <div class="rounded-lg shadow-lg px-4 py-3 text-sm font-medium flex items-center gap-2
            {{ $flashType === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white' }}">
            @if($flashType === 'success')
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            @else
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            @endif
            {{ $flashMessage }}
        </div>
    </div>
    @endif

    {{-- ─── Main Content ────────────────────────────────────────── --}}
    <main class="flex-1 pb-28">

        {{-- ═══ STEP: MENU ══════════════════════════════════════════ --}}
        @if($step === 'menu')

        {{-- Active Order Persistence Banner --}}
        <div x-data="{
                activeToken: localStorage.getItem('active_self_order_token'),
                activeType: localStorage.getItem('active_self_order_type') || 'track'
             }"
             x-show="activeToken"
             x-cloak
             class="bg-gray-900 text-white px-4 py-2.5 text-xs flex justify-between items-center shadow-md border-b border-gray-800 sticky top-[80px] z-40">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping shrink-0"></span>
                <span class="font-medium text-gray-200">Pesanan Anda sedang berlangsung</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a :href="`{{ url('/self-order') }}/${activeType}/${activeToken}`"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-3 py-1 rounded-lg text-xs transition shadow-xs">
                    Lihat Status →
                </a>
                <button @click="localStorage.removeItem('active_self_order_token'); activeToken = null" class="text-gray-400 hover:text-white font-bold p-1">
                    ✕
                </button>
            </div>
        </div>

        {{-- Category Filter --}}
        <div class="bg-white border-b sticky top-[80px] z-40 overflow-x-auto flex gap-2 px-4 py-3 scrollbar-hide">
            <button wire:click="$set('selectedCategoryId', null)"
                class="flex-shrink-0 px-4 py-1.5 rounded-lg text-sm font-medium border transition
                    {{ is_null($selectedCategoryId) ? 'bg-brand text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">
                Semua
            </button>
            @foreach($this->categories as $cat)
            <button wire:click="$set('selectedCategoryId', {{ $cat->id }})"
                class="flex-shrink-0 px-4 py-1.5 rounded-lg text-sm font-medium border transition
                    {{ $selectedCategoryId === $cat->id ? 'bg-brand text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:border-blue-300' }}">
                {{ $cat->name }}
            </button>
            @endforeach
        </div>

        {{-- Search --}}
        <div class="px-4 pt-3">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input wire:model.live.debounce.400ms="searchQuery"
                    type="search" placeholder="Cari menu..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
        </div>

        {{-- Products Grid --}}
        <div class="px-4 pt-4 grid grid-cols-2 gap-3">
            @forelse($this->products as $product)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition group flex flex-col justify-between
                {{ !$product->is_active ? 'opacity-50 pointer-events-none' : '' }}"
                wire:key="product-{{ $product->id }}">

                <div>
                    {{-- Product Image --}}
                    <div class="relative aspect-[4/3] bg-gray-100">
                        @if($product->image_url ?? null)
                            <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-red-50 to-orange-50">
                                <svg class="w-10 h-10 text-red-200" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M18.06 22.99h1.66c.84 0 1.53-.64 1.63-1.46L23 5.05h-5V1h-1.97v4.05h-4.97l.3 2.34c1.71.47 3.31 1.32 4.27 2.26 1.44 1.42 2.43 2.89 2.43 5.29v8.05zM1 21.99V21h15.03v.99c0 .55-.45 1-1.01 1H2.01c-.56 0-1.01-.45-1.01-1zm15.03-7c0-8.17-15.03-8.17-15.03 0h15.03zM1.02 17h15v2h-15z"/>
                                </svg>
                            </div>
                        @endif

                        {{-- Stock Badge --}}
                        @if($product->track_stock)
                        @php $avail = $product->getAvailableStock(); @endphp
                        @if($avail <= 0)
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                            <span class="text-white font-bold text-xs bg-black/70 px-2.5 py-1 rounded-lg">Habis</span>
                        </div>
                        @elseif($avail <= 5)
                        <span class="absolute top-2 right-2 bg-amber-500 text-white text-xs font-bold px-2 py-0.5 rounded-lg shadow">
                            Sisa {{ $avail }}
                        </span>
                        @endif
                        @endif
                    </div>

                    {{-- Info --}}
                    <div class="p-3">
                        <p class="font-semibold text-gray-800 text-sm leading-tight line-clamp-2">{{ $product->name }}</p>
                        <p class="text-brand font-bold text-sm mt-1">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    </div>
                </div>

                {{-- Add Button --}}
                <div class="px-3 pb-3">
                    @if($product->modifierGroups->count() > 0)
                    <button wire:click="openModifierModal({{ $product->id }})"
                        class="w-full bg-brand text-white rounded-xl py-2 text-xs font-semibold hover:bg-blue-700 active:scale-95 transition flex items-center justify-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Pilih Opsi
                    </button>
                    @else
                    <button wire:click="addToCart({{ $product->id }})"
                        class="w-full bg-brand text-white rounded-xl py-2 text-xs font-semibold hover:bg-blue-700 active:scale-95 transition flex items-center justify-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah
                    </button>
                    @endif
                </div>
            </div>
            @empty
            <div class="col-span-2 text-center py-12 text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="font-medium">Menu tidak ditemukan</p>
            </div>
            @endforelse
        </div>

        {{-- Floating Cart Bar --}}
        @if($this->cartCount > 0)
        <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md px-4 pb-4 z-50">
            <button wire:click="goToCheckout"
                class="w-full bg-brand text-white rounded-2xl shadow-2xl py-3.5 px-5 flex items-center justify-between font-semibold text-sm active:scale-98 transition">
                <span class="bg-white/20 text-white text-xs font-bold px-2.5 py-1 rounded-lg">
                    {{ $this->cartCount }} item
                </span>
                <span class="font-bold">Lihat Pesanan</span>
                <span class="font-bold">Rp {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
            </button>
        </div>
        @endif

        {{-- ═══ STEP: CHECKOUT ════════════════════════════════════════ --}}
        @elseif($step === 'checkout')

        <div class="px-4 pt-4 space-y-4">

            {{-- Cart Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm flex justify-between items-center">
                    <span>Ringkasan Pesanan ({{ $this->cartCount }} item)</span>
                    <button wire:click="clearCart" class="text-xs text-red-500 hover:text-red-700 font-medium">Kosongkan</button>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach($this->cartItems as $item)
                    <div class="px-4 py-3 flex items-start gap-3" wire:key="cart-item-{{ $item['cart_key'] }}">
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 text-sm">{{ $item['product_name'] }}</p>
                            @if(!empty($item['modifiers']))
                            <p class="text-xs text-gray-400 mt-0.5">
                                {{ collect($item['modifiers'])->pluck('modifier_name')->join(', ') }}
                            </p>
                            @endif
                            @if($item['notes'])
                            <p class="text-xs text-gray-400 italic mt-0.5">Catatan: {{ $item['notes'] }}</p>
                            @endif
                            <p class="text-xs text-brand font-bold mt-1">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</p>
                        </div>

                        {{-- Quantity Control & Delete Button --}}
                        <div class="flex items-center gap-1.5">
                            {{-- Minus --}}
                            <button wire:click="updateQuantity('{{ $item['cart_key'] }}', -1)"
                                class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 flex items-center justify-center transition font-bold text-base leading-none">
                                −
                            </button>

                            <span class="w-6 text-center font-bold text-sm">{{ $item['quantity'] }}</span>

                            {{-- Plus --}}
                            <button wire:click="updateQuantity('{{ $item['cart_key'] }}', 1)"
                                class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-emerald-100 text-gray-600 hover:text-emerald-600 flex items-center justify-center transition font-bold text-base leading-none">
                                +
                            </button>

                            {{-- Delete Button --}}
                            <button wire:click="confirmRemoveFromCart('{{ $item['cart_key'] }}')"
                                title="Hapus produk ini"
                                class="w-7 h-7 rounded-lg bg-red-50 hover:bg-red-100 text-red-500 hover:text-red-700 flex items-center justify-center transition ml-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Totals --}}
                <div class="border-t border-gray-100 px-4 py-3 space-y-1.5 bg-gray-50/50">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($this->cartSubtotal, 0, ',', '.') }}</span>
                    </div>
                    @if($this->taxRate > 0)
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>Pajak ({{ $this->taxRate }}%)</span>
                        <span>Rp {{ number_format($this->taxAmount, 0, ',', '.') }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-base text-gray-900 pt-2 border-t border-gray-200">
                        <span>Total</span>
                        <span class="text-brand">Rp {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>

            {{-- Customer Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">
                    Data Pemesan
                </div>
                <div class="px-4 py-4 space-y-3">
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nama <span class="text-red-500">*</span></label>
                        <input wire:model="customerName" type="text" placeholder="Nama Anda"
                            class="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('customerName') border-red-500 bg-red-50/30 @else border-gray-200 @enderror">
                        @error('customerName') <p class="text-red-600 font-semibold text-xs mt-1 flex items-center gap-1"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">No. HP <span class="text-red-500">*</span></label>
                        <input wire:model="customerPhone" type="tel" placeholder="08xxxxxxxxxx"
                            class="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('customerPhone') border-red-500 bg-red-50/30 @else border-gray-200 @enderror">
                        @error('customerPhone') <p class="text-red-600 font-semibold text-xs mt-1 flex items-center gap-1"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Email (Opsional — untuk struk digital)</label>
                        <input wire:model="customerEmail" type="email" placeholder="email@contoh.com"
                            class="mt-1 w-full rounded-xl border px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('customerEmail') border-red-500 bg-red-50/30 @else border-gray-200 @enderror">
                        @error('customerEmail') <p class="text-red-600 font-semibold text-xs mt-1 flex items-center gap-1"><svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Catatan Pesanan</label>
                        <textarea wire:model="notes" placeholder="Permintaan khusus..." rows="2"
                            class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>
                </div>
            </div>

            {{-- Order Type --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">Tipe Order</div>
                <div class="px-4 py-4 grid grid-cols-3 gap-2">
                    @foreach(['dine_in' => 'Makan di Sini', 'take_away' => 'Bawa Pulang', 'pick_up' => 'Ambil Sendiri'] as $type => $label)
                    <button wire:click="$set('orderType', '{{ $type }}')"
                        class="py-2.5 rounded-xl text-xs font-semibold border transition
                            {{ $orderType === $type ? 'bg-brand text-white border-blue-600 shadow-sm' : 'bg-gray-50 text-gray-600 border-gray-200' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Payment Method --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-700 text-sm">Metode Pembayaran</div>
                <div class="px-4 py-4 grid grid-cols-2 gap-3">
                    <button wire:click="$set('paymentMethod', 'qris')"
                        class="flex flex-col items-center gap-2 py-4 rounded-xl border-2 transition
                            {{ $paymentMethod === 'qris' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 bg-gray-50' }}">
                        <div class="w-10 h-10 rounded-xl {{ $paymentMethod === 'qris' ? 'bg-blue-100 text-brand' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold {{ $paymentMethod === 'qris' ? 'text-brand' : 'text-gray-600' }}">QRIS</span>
                        <span class="text-[11px] text-gray-400">Bayar sekarang</span>
                    </button>

                    <button wire:click="$set('paymentMethod', 'cash_on_counter')"
                        class="flex flex-col items-center gap-2 py-4 rounded-xl border-2 transition
                            {{ $paymentMethod === 'cash_on_counter' ? 'border-blue-500 bg-blue-50/50' : 'border-gray-200 bg-gray-50' }}">
                        <div class="w-10 h-10 rounded-xl {{ $paymentMethod === 'cash_on_counter' ? 'bg-blue-100 text-brand' : 'bg-gray-100 text-gray-400' }} flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <span class="text-xs font-bold {{ $paymentMethod === 'cash_on_counter' ? 'text-brand' : 'text-gray-600' }}">Bayar di Kasir</span>
                        <span class="text-[11px] text-gray-400">Bayar saat ambil</span>
                    </button>
                </div>
            </div>

            {{-- Honeypot (anti-bot) --}}
            <input type="text" name="website_url" tabindex="-1" aria-hidden="true"
                class="absolute opacity-0 w-0 h-0 overflow-hidden" autocomplete="off">

            {{-- Submit --}}
            <div class="pb-6">
                <button wire:click.debounce.500ms="placeOrder"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-80 cursor-not-allowed pointer-events-none"
                    wire:target="placeOrder"
                    class="w-full bg-brand hover:bg-blue-700 active:scale-[0.99] text-white rounded-2xl py-4 px-6 font-extrabold text-base shadow-xl shadow-blue-600/25 transition-all duration-200 min-h-[56px] relative overflow-hidden">
                    {{-- Normal state --}}
                    <span wire:loading.remove wire:target="placeOrder" class="flex items-center justify-center gap-2.5">
                        @if($paymentMethod === 'qris')
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                            </svg>
                            <span>Bayar Sekarang · Rp {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                        @else
                            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span>Pesan Sekarang · Rp {{ number_format($this->cartTotal, 0, ',', '.') }}</span>
                        @endif
                    </span>
                    {{-- Loading overlay: display none/flex ditoggle Livewire sendiri lewat modifier .flex --}}
                    <span wire:loading.flex wire:target="placeOrder" class="btn-loading-overlay">
                        <svg class="animate-spin w-5 h-5 text-white flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-100" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="font-bold tracking-wide leading-none">Memproses Pesanan Anda...</span>
                    </span>
                </button>
            </div>
        </div>
        @endif

    </main>

    {{-- ─── Modal: Modifier ──────────────────────────────────────── --}}
    @if($showModifierModal && $modifierProductId)
    @php $modalProduct = $this->products->firstWhere('id', $modifierProductId); @endphp
    @if($modalProduct)
    <div class="fixed inset-0 z-50 flex items-end justify-center" x-data>
        <div class="absolute inset-0 bg-black/60 backdrop-blur-xs" wire:click="closeModifierModal"></div>
        <div class="relative w-full max-w-md bg-white rounded-t-3xl shadow-2xl max-h-[85vh] overflow-y-auto z-10 border-t border-gray-100">
            <div class="p-4 border-b sticky top-0 bg-white z-10 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-gray-900 text-lg leading-tight">{{ $modalProduct->name }}</h3>
                    <p class="text-xs text-gray-400">Harga dasar: Rp {{ number_format($modalProduct->price, 0, ',', '.') }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-gray-400 block font-medium">Estimasi Total</span>
                    <span class="text-brand font-black text-base">Rp {{ number_format($this->modalTotalPrice, 0, ',', '.') }}</span>
                </div>
            </div>

            <div class="p-4 space-y-5">
                @foreach($modalProduct->modifierGroups as $group)
                <div class="bg-gray-50/70 p-3.5 rounded-2xl border border-gray-100">
                    <div class="flex items-center justify-between mb-2.5">
                        <p class="font-bold text-sm text-gray-800">{{ $group->name }}</p>
                        <div class="flex items-center gap-1.5">
                            @if($group->is_required)
                                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-md">Wajib</span>
                            @endif
                            <span class="bg-gray-200 text-gray-600 text-[10px] font-medium px-2 py-0.5 rounded-md">
                                {{ $group->selection_type === 'single' ? 'Pilih 1' : 'Pilih Banyak' }}
                            </span>
                        </div>
                    </div>

                    <div class="space-y-2">
                        @foreach($group->activeModifiers as $modifier)
                        @php
                            $modQty = $modifierQuantities[$modifier->id] ?? 0;
                            $isSelected = $group->selection_type === 'single'
                                ? (isset($singleModifiers[$group->id]) && $singleModifiers[$group->id] == $modifier->id)
                                : ($modQty > 0 || !empty($selectedModifiers[$modifier->id]));
                        @endphp

                        @if($group->selection_type === 'single')
                        {{-- Single choice: Radio button --}}
                        <label class="flex items-center justify-between p-3 rounded-xl border cursor-pointer transition select-none
                            {{ $isSelected ? 'border-blue-500 bg-blue-50/70 ring-1 ring-blue-500/20' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <div class="flex items-center gap-3">
                                <input type="radio"
                                    name="group_single_{{ $group->id }}"
                                    wire:model.live="singleModifiers.{{ $group->id }}"
                                    value="{{ $modifier->id }}"
                                    class="accent-blue-600 w-4 h-4">
                                <span class="text-sm font-medium text-gray-800">{{ $modifier->name }}</span>
                            </div>
                            @if($modifier->price_adjustment > 0)
                            <span class="text-xs text-brand font-bold bg-white px-2 py-1 rounded-md border border-gray-100">
                                +Rp {{ number_format($modifier->price_adjustment, 0, ',', '.') }}
                            </span>
                            @endif
                        </label>
                        @else
                        {{-- Multiple choice: Quantity Stepper --}}
                        <div class="flex items-center justify-between p-3 rounded-xl border transition select-none
                            {{ $modQty > 0 ? 'border-blue-500 bg-blue-50/70 ring-1 ring-blue-500/20' : 'border-gray-200 bg-white hover:border-gray-300' }}">
                            <div class="flex flex-col min-w-0 pr-2">
                                <span class="text-sm font-semibold text-gray-800 truncate">{{ $modifier->name }}</span>
                                @if($modifier->price_adjustment > 0)
                                <span class="text-xs text-brand font-bold">
                                    +Rp {{ number_format($modifier->price_adjustment, 0, ',', '.') }}
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                @if($modQty > 0)
                                <button type="button" wire:click="updateModifierQty({{ $modifier->id }}, -1)"
                                    class="w-7 h-7 rounded-lg bg-white border border-gray-300 flex items-center justify-center font-bold text-gray-700 shadow-xs hover:bg-red-50 hover:text-red-600 transition text-sm">
                                    −
                                </button>
                                <span class="w-6 text-center font-bold text-sm text-blue-700">{{ $modQty }}</span>
                                <button type="button" wire:click="updateModifierQty({{ $modifier->id }}, 1)"
                                    class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center font-bold shadow-xs hover:bg-blue-700 transition text-sm">
                                    +
                                </button>
                                @else
                                <button type="button" wire:click="updateModifierQty({{ $modifier->id }}, 1)"
                                    class="px-3 py-1 rounded-lg bg-gray-100 hover:bg-blue-50 hover:text-blue-600 border border-gray-200 text-xs font-semibold text-gray-600 transition flex items-center gap-1">
                                    <span>+ Tambah</span>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endif
                        @endforeach
                    </div>
                </div>
                @endforeach

                {{-- Quantity --}}
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-2xl border border-gray-100">
                    <span class="font-bold text-gray-800 text-sm">Jumlah</span>
                    <div class="flex items-center gap-3">
                        <button wire:click="$set('modalQuantity', {{ max(1, $modalQuantity - 1) }})"
                            class="w-9 h-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center font-bold text-lg shadow-sm hover:bg-blue-50 transition text-gray-700">−</button>
                        <span class="font-bold text-lg w-6 text-center text-gray-900">{{ $modalQuantity }}</span>
                        <button wire:click="$set('modalQuantity', {{ min(20, $modalQuantity + 1) }})"
                            class="w-9 h-9 rounded-lg bg-white border border-gray-200 flex items-center justify-center font-bold text-lg shadow-sm hover:bg-emerald-50 transition text-gray-700">+</button>
                    </div>
                </div>

                <button wire:click="addToCartFromModal"
                    class="w-full bg-brand text-white rounded-2xl py-4 font-bold text-base hover:bg-blue-700 active:scale-98 transition shadow-lg shadow-blue-600/20 flex items-center justify-between px-6">
                    <span>Tambah ke Pesanan</span>
                    <span>Rp {{ number_format($this->modalTotalPrice, 0, ',', '.') }}</span>
                </button>
            </div>
        </div>
    </div>
    @endif
    @endif

    {{-- ─── Modal: Konfirmasi Hapus Produk dari Keranjang (Custom, Tanpa Browser Confirm) ── --}}
    @if($showDeleteConfirmModal)
    <div class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4" x-data>
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs p-5 text-center transform transition-all scale-100">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-3 text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>
            <h3 class="font-bold text-gray-900 text-base mb-1">Hapus dari Keranjang?</h3>
            <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                Apakah Anda yakin ingin menghapus <span class="font-semibold text-gray-800">"{{ $deletingProductName }}"</span> dari keranjang pesanan?
            </p>
            <div class="flex gap-2">
                <button wire:click="cancelRemoveFromCart"
                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-xl text-xs transition">
                    Batal
                </button>
                <button wire:click="executeRemoveFromCart"
                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl text-xs transition shadow-md shadow-red-600/30">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
