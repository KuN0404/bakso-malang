<div class="fixed inset-0 w-full flex bg-gray-100 overflow-hidden" style="height: 100dvh;"
    @print-receipt.window="console.log('Alpine print-receipt triggered'); setTimeout(() => window.print(), 600)"
    @print-shift-receipt.window="console.log('Alpine print-shift-receipt triggered'); setTimeout(() => window.print(), 600)"
    @cart-updated.window="new BroadcastChannel('pos_channel_' + {{ auth()->id() }}).postMessage($event.detail.data)"
    x-data="{
        showProductModal: false, 
        selectedProduct: null, 
        showCartMobile: false,
        showMobileMenu: false,
        // Modifier selection
        showModifierModal: false,
        modifierProduct: null,
        selectedModifiers: {},
        modifierTotal: 0,
        isThrottled: false,
        showConfirmCloseShift: false,
        showSelfOrderModal: false,
        
        openModifierModal(product) {
            this.modifierProduct = product;
            this.selectedModifiers = {};
            this.modifierTotal = 0;
            // Set defaults for single selection groups (first option selected by default)
            if (product.modifier_groups) {
                product.modifier_groups.forEach(group => {
                    if (group.selection_type === 'single' && group.modifiers && group.modifiers.length > 0) {
                        this.selectedModifiers[group.id] = [{ ...group.modifiers[0], qty: 1 }];
                    }
                });
            }
            this.calculateModifierTotal();
            this.showModifierModal = true;
        },
        selectModifier(groupId, modifier, selectionType) {
            if (selectionType === 'single') {
                // Single selection: always set to chosen modifier (cannot be deselected to empty)
                this.selectedModifiers[groupId] = [{ ...modifier, qty: 1 }];
            } else {
                if (!this.selectedModifiers[groupId]) {
                    this.selectedModifiers[groupId] = [];
                }
                const index = this.selectedModifiers[groupId].findIndex(m => m.id === modifier.id);
                if (index > -1) {
                    this.selectedModifiers[groupId].splice(index, 1);
                } else {
                    this.selectedModifiers[groupId].push({ ...modifier, qty: 1 });
                }
            }
            this.calculateModifierTotal();
        },
        changeModifierQty(groupId, modifierId, delta) {
            if (!this.selectedModifiers[groupId]) return;
            const group = this.modifierProduct?.modifier_groups?.find(g => g.id === groupId);
            if (group && group.selection_type === 'single') return;

            const item = this.selectedModifiers[groupId].find(m => m.id === modifierId);
            if (item) {
                const newQty = (item.qty || 1) + delta;
                if (newQty <= 0) {
                    const idx = this.selectedModifiers[groupId].findIndex(m => m.id === modifierId);
                    if (idx > -1) this.selectedModifiers[groupId].splice(idx, 1);
                } else {
                    item.qty = newQty;
                }
                this.calculateModifierTotal();
            }
        },
        getModifierQty(groupId, modifierId) {
            if (!this.selectedModifiers[groupId]) return 0;
            const item = this.selectedModifiers[groupId].find(m => m.id === modifierId);
            return item ? (item.qty || 1) : 0;
        },
        isModifierSelected(groupId, modifierId) {
            if (!this.selectedModifiers[groupId]) return false;
            return this.selectedModifiers[groupId].some(m => m.id === modifierId);
        },
        calculateModifierTotal() {
            let total = 0;
            Object.values(this.selectedModifiers).forEach(modifiers => {
                modifiers.forEach(m => {
                    total += (Number(m.price_adjustment) || 0) * (Number(m.qty) || 1);
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
                    result[m.id] = {
                        name: m.name,
                        price: Number(m.price_adjustment) || 0,
                        qty: Number(m.qty) || 1,
                        component_id: m.component_id || null
                    };
                });
            });
            return result;
        },
        addWithModifiers() {
            if (this.isThrottled) return;

            if (this.modifierProduct) {
                // Ensure all single selection groups have a selection
                if (this.modifierProduct.modifier_groups) {
                    for (const group of this.modifierProduct.modifier_groups) {
                        if (group.selection_type === 'single') {
                            const sel = this.selectedModifiers[group.id];
                            if (!sel || sel.length === 0) {
                                alert('Silakan pilih opsi untuk ' + group.name);
                                return;
                            }
                        }
                    }
                }

                this.isThrottled = true;
                $wire.addToCart(this.modifierProduct.id, this.getModifiersForCart());
                this.showModifierModal = false;
                this.modifierProduct = null;
                this.selectedModifiers = {};
                setTimeout(() => { this.isThrottled = false; }, 300);
            }
        }
    }"
    @keydown.f1.window.prevent="$wire.openPaymentModal()"
    @keydown.f2.window.prevent="$wire.processPayment()"
    @keydown.f3.window.prevent="if(confirm('Hapus semua item?')) $wire.clearCart()"
    @keydown.f4.window.prevent="$wire.openCloseShiftModal()"
    @keydown.f5.window.prevent="$wire.openPendingModal()"
    @keydown.escape.window="$wire.closePaymentModal(); $wire.closeReceiptModal(); $wire.set('showCloseShiftModal', false); $wire.set('showShiftReceiptModal', false); $wire.closePendingModal(); showModifierModal = false; showMobileMenu = false; showSelfOrderModal = false">
    
    <!-- Unclosed Previous Shift Blocking Modal -->
    @if($this->unclosedPreviousShift && !$showUnclosedShiftModal)
        <div class="fixed inset-0 bg-black/70 z-[100] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl">
                <div class="p-6 border-b bg-red-50 rounded-t-2xl">
                    <div class="flex items-center gap-3 text-red-600">
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <x-lucide name="alert-triangle" class="w-6 h-6" />
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
                            <x-lucide name="info" class="w-5 h-5 flex-shrink-0 mt-0.5" />
                            <span>POS akan diblokir sampai shift ini ditutup. Silakan tutup shift atau hubungi Super Admin.</span>
                        </p>
                    </div>
                </div>
                
                <div class="p-6 border-t bg-gray-50 rounded-b-2xl">
                    <button 
                        wire:click="openClosePreviousShiftModal"
                        class="w-full py-4 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <x-lucide name="clock" class="w-5 h-5" />
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
                            <x-lucide name="clock" class="w-6 h-6 text-red-600" />
                            Tutup Shift {{ $this->unclosedShiftModalData?->started_at?->format('d/m/Y') }}
                        </h3>
                    </div>
                    <p class="text-sm text-red-500 mt-1">Shift ini akan ditandai sebagai "Ditutup Terlambat"</p>
                </div>
                <div class="p-6 space-y-4 overflow-y-auto flex-1 custom-scroll">
                    <!-- Opening Cash -->
                    <div x-data="moneyInput({{ $openingCash }}, 'openingCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modal Awal (Cash)</label>
                        <input 
                            id="openingCashInputPrev"
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
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
                                <x-lucide name="plus" class="w-4 h-4" /> Tambah
                            </button>
                        </div>
                        @foreach($expenses as $index => $expense)
                            <div class="flex gap-2 mb-2" wire:key="prev-expense-{{ $index }}">
                                <input type="text" wire:model="expenses.{{ $index }}.description" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Keterangan">
                                <div x-data="moneyInput({{ $expense['amount'] ?? 0 }}, 'expenses.{{ $index }}.amount')" class="w-32">
                                    <input 
                                        type="text" 
                                        inputmode="numeric" 
                                        x-model="formatted" 
                                        @input="onInput($event)" 
                                        @focus="$event.target.select()"
                                        @blur="syncToWire()"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-right" 
                                        placeholder="0"
                                    >
                                </div>
                                <button wire:click="removeExpense({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <x-lucide name="trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <!-- Actual Cash (Physical in drawer) -->
                    <div x-data="moneyInput({{ $actualCash }}, 'actualCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Uang Fisik Tunai di Laci
                        </label>
                        <input 
                            id="actualCashInputPrev"
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <x-lucide name="info" class="w-3 h-3 shrink-0" />
                            Hitung HANYA uang kertas & koin di laci cash drawer.
                        </p>
                    </div>

                    <!-- Actual Non-Cash (QRIS/Transfer verified from bank) -->
                    <div x-data="moneyInput({{ $actualNonCash }}, 'actualNonCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Uang Non-Tunai (QRIS / Transfer / EDC)
                        </label>
                        <input
                            id="actualNonCashInputPrev"
                            type="text"
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="0"
                        >
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <x-lucide name="info" class="w-3 h-3 shrink-0" />
                            Cocokkan dengan mutasi rekening / dashboard QRIS sebelum mengisi.
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea wire:model="closeNotes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Catatan opsional"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t bg-gray-50 flex gap-3 flex-none rounded-b-2xl">
                    <button wire:click="$set('showUnclosedShiftModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Kembali</button>
                    <button 
                        @click="
                            let opEl = document.getElementById('openingCashInputPrev');
                            let acEl = document.getElementById('actualCashInputPrev');
                            let ancEl = document.getElementById('actualNonCashInputPrev');
                            if (!opEl || !acEl || !ancEl) {
                                $wire.closePreviousShift();
                            } else {
                                let op = opEl.value;
                                let ac = acEl.value;
                                let anc = ancEl.value;
                                if(op.trim() === '' || ac.trim() === '' || anc.trim() === '') {
                                    $dispatch('notify', { type: 'error', message: 'Harap isi semua kolom wajib (isi 0 jika tidak ada)' });
                                } else {
                                    $wire.closePreviousShift();
                                }
                            }
                        "
                        wire:loading.attr="disabled"
                        class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <x-lucide name="check" class="w-5 h-5" />
                        Tutup Shift
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Left Panel: Products -->
    <div class="flex-1 flex flex-col bg-gray-50 min-w-0 overflow-x-hidden">
        <header class="bg-white px-4 md:px-6 py-3 border-b flex justify-between items-center sticky top-0 z-20 gap-4">
            <div class="flex items-center gap-3 flex-shrink-0">
                @if($this->logoType === 'full' && $this->logoFull)
                    <img src="{{ asset('storage/' . $this->logoFull) }}" class="h-10 w-auto max-w-[160px] object-contain">
                @else
                    @if($this->logoWeb)
                        <img src="{{ asset('storage/' . $this->logoWeb) }}" class="w-10 h-10 object-cover rounded-lg shadow-md">
                    @else
                        <div class="w-10 h-10 bg-primary-600 rounded-lg flex items-center justify-center shadow-lg shadow-primary-500/30">
                            <x-lucide name="soup" class="w-6 h-6 text-white" />
                        </div>
                    @endif
                    <div class="hidden sm:block">
                        <h1 class="font-bold text-gray-800 leading-tight">Bakso Malang</h1>
                        <p class="text-xs text-gray-500">Point of Sale</p>
                    </div>
                @endif
            </div>
 
            <!-- Desktop Action Buttons (mulai dari xl: 1280px — di bawah itu, termasuk tablet
                 landscape seperti iPad yang persis 1024px, semua tombol ini tidak muat dan
                 bikin header berantakan; pakai menu ringkas ala mobile sampai layar benar-benar lega) -->
            <div class="hidden xl:flex items-center gap-3 justify-end select-none" wire:poll.10000ms.visible="checkForNewSelfOrders">
                <button onclick="window.open('{{ route('pos.display') }}', 'CustomerDisplay', 'width=1280,height=800,menubar=no,toolbar=no,location=no,status=no')" 
                        class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg transition-colors border border-indigo-200" 
                        title="Buka Layar Pelanggan">
                    <x-lucide name="monitor" class="w-4 h-4" />
                </button>
 
                <!-- Pending Orders Button -->
                <button wire:click="openPendingModal" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-colors border border-amber-200" title="Daftar Pesanan Pending (F5)">
                    <x-lucide name="clock" class="w-4 h-4 text-amber-600" />
                    <span class="text-xs font-bold hidden xl:inline">Pending</span>
                    <span class="bg-amber-200 text-amber-800 text-xs py-0.5 px-2 rounded-full font-bold">
                        {{ count($this->pendingCarts) }}
                    </span>
                </button>

                <!-- Pesan Mandiri Modal Button -->
                @can('view_self_orders')
                <button @click="showSelfOrderModal = true"
                    class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition-colors border border-emerald-200"
                    title="Pesanan Mandiri Pelanggan">
                    <x-lucide name="smartphone" class="w-4 h-4 text-emerald-600" />
                    <span class="text-xs font-bold hidden xl:inline">Pesan Mandiri</span>
                    @if($this->selfOrdersCount > 0)
                        <span class="bg-emerald-600 text-white text-xs py-0.5 px-2 rounded-full font-bold animate-pulse">
                            {{ $this->selfOrdersCount }}
                        </span>
                    @else
                        <span class="bg-emerald-200 text-emerald-800 text-xs py-0.5 px-2 rounded-full font-bold">
                            0
                        </span>
                    @endif
                </button>
                @endcan

                <!-- History Buttons Group -->
                <div class="flex items-center flex-shrink-0">
                    <button wire:click="openHistoryModal" class="flex items-center gap-2 px-3 py-2 bg-blue-50 hover:bg-blue-100 rounded-l-lg text-blue-700 transition-colors border border-blue-200 border-r-0" title="Riwayat Transaksi">
                        <x-lucide name="history" class="w-4 h-4 text-blue-600" />
                        <span class="bg-blue-200 text-blue-800 text-xs py-0.5 px-2 rounded-full font-bold">
                            {{ $this->todayTransactions->count() }}
                        </span>
                    </button>
                    <button wire:click="openReturnHistoryModal" class="flex items-center gap-2 px-3 py-2 bg-orange-50 hover:bg-orange-100 rounded-r-lg text-orange-700 transition-colors border border-orange-200" title="Riwayat Retur">
                        <x-lucide name="list" class="w-4 h-4 text-orange-600" />
                        <span class="bg-orange-200 text-orange-800 text-xs py-0.5 px-2 rounded-full font-bold">
                            {{ $this->todayReturns->count() }}
                        </span>
                    </button>
                </div>
                
                <!-- Return Action Button -->
                <button wire:click="openReturnModal" class="flex-shrink-0 flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 rounded-lg text-red-600 transition-colors border border-red-200 shadow-sm">
                    <x-lucide name="rotate-ccw" class="w-4 h-4" />
                    <span class="text-sm font-bold uppercase tracking-wide">Retur</span>
                </button>

 
                @if($this->todayShift && $this->todayShift->status === 'open')
                    <button wire:click="openCloseShiftModal" class="flex-shrink-0 flex items-center gap-2 px-3 py-2 bg-red-50 hover:bg-red-50 text-red-600 rounded-lg transition-colors border border-red-100">
                        <x-lucide name="power" class="w-4 h-4" />
                        <span class="text-sm font-medium hidden md:inline">Tutup Shift (F4)</span>
                    </button>
                @endif
                
                <div class="h-6 w-px bg-gray-200 mx-1 flex-shrink-0"></div>
 
                <a href="{{ route('admin.dashboard') }}" class="flex-shrink-0 p-2 text-gray-400 hover:text-primary-600 hover:bg-primary-50 rounded-lg transition-colors" title="Dashboard">
                    <x-lucide name="layout-dashboard" class="w-5 h-5" />
                </a>
 
                @php
                    $todayShift = $this->todayShift;
                    $shiftCount = $this->shiftsTodayCount;
                    $shiftIsOpen = $todayShift && $todayShift->status === 'open';
                @endphp
                <button type="button"
                    @if(!$shiftIsOpen) wire:click="openShiftPrompt" @endif
                    title="{{ $shiftIsOpen ? auth()->user()->name : 'Klik untuk membuka shift' }}"
                    class="flex-shrink-0 flex items-center gap-2 bg-gray-50 px-3 py-1 rounded-lg border border-gray-100 select-none text-left {{ $shiftIsOpen ? 'cursor-default' : 'hover:bg-amber-50 hover:border-amber-200 transition-colors cursor-pointer' }}">
                    <div class="w-8 h-8 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-bold text-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="flex flex-col text-left">
                        <span class="font-bold text-gray-800 text-sm leading-tight truncate max-w-[120px]">
                            {{ auth()->user()->name }}
                        </span>
                        @if($shiftIsOpen)
                            <span class="text-[10px] text-green-600 font-semibold flex items-center gap-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse shrink-0"></span>
                                Shift Aktif (Ke-{{ $shiftCount }})
                            </span>
                        @elseif($shiftCount > 0)
                            <span class="text-[10px] text-amber-600 font-semibold flex items-center gap-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                Shift Selesai · Klik buka shift baru
                            </span>
                        @else
                            <span class="text-[10px] text-amber-600 font-semibold flex items-center gap-0.5">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span>
                                Klik untuk mulai shift
                            </span>
                        @endif
                    </div>
                </button>
            </div>

            <!-- Mobile & Tablet Menu Trigger & Pesan Mandiri Button -->
            <div class="xl:hidden flex items-center gap-2 select-none">
                @can('view_self_orders')
                <button @click="showSelfOrderModal = true"
                    class="relative p-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition-all shadow-xs border border-emerald-200 flex items-center justify-center"
                    title="Pesanan Mandiri">
                    <x-lucide name="smartphone" class="w-5 h-5 text-emerald-600" />
                    @if($this->selfOrdersCount > 0)
                        <span class="absolute -top-1.5 -right-1.5 bg-emerald-600 text-white text-[10px] font-extrabold px-1.5 py-0.5 rounded-full shadow animate-pulse min-w-[18px] text-center">
                            {{ $this->selfOrdersCount }}
                        </span>
                    @endif
                </button>
                @endcan

                <button @click="showMobileMenu = true" class="p-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl transition-all shadow-sm flex items-center justify-center" title="Menu POS" aria-label="Buka Menu POS">
                    <x-lucide name="more-vertical" class="w-5 h-5" />
                </button>
            </div>
        </header>

        <!-- Mobile & Tablet Menu Modal Overlay.
             z-[70] (bukan z-50) — panel keranjang di kanan (baris ~963) juga z-50 dan tampil
             lebih akhir di DOM, jadi kalau sama-sama z-50 dia menang tumpang tindih dan menutupi
             menu ini di lebar tablet (dimana keranjang jadi sidebar statis, bukan drawer). -->
        <div
            x-show="showMobileMenu"
            x-cloak
            class="fixed inset-0 z-[70] xl:hidden"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
        >
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="showMobileMenu = false"></div>
            
            <!-- Bottom Sheet Modal Content -->
            <div 
                class="fixed bottom-0 inset-x-0 bg-white rounded-t-3xl shadow-2xl p-6 space-y-4 max-h-[85vh] overflow-y-auto flex flex-col"
                x-show="showMobileMenu"
                x-transition:enter="transition ease-out duration-300 transform"
                x-transition:enter-start="translate-y-full"
                x-transition:enter-end="translate-y-0"
                x-transition:leave="transition ease-in duration-200 transform"
                x-transition:leave-start="translate-y-0"
                x-transition:leave-end="translate-y-full"
            >
                <div class="flex justify-between items-center pb-2 border-b">
                    <div class="flex items-center gap-2">
                        <x-lucide name="user" class="w-5 h-5 text-gray-400" />
                        <span class="font-bold text-gray-800">{{ auth()->user()->name }}</span>
                    </div>
                    <button @click="showMobileMenu = false" class="p-2 hover:bg-gray-100 rounded-full text-gray-500">
                        <x-lucide name="x" class="w-6 h-6" />
                    </button>
                </div>

                <div class="grid grid-cols-1 gap-3 py-2">
                    @unless($shiftIsOpen)
                    <button
                        wire:click="openShiftPrompt(); showMobileMenu = false"
                        class="w-full flex items-center gap-3 px-4 py-3.5 bg-green-50 hover:bg-green-100 text-green-700 rounded-xl transition-colors font-medium border border-green-100"
                    >
                        <x-lucide name="power" class="w-5 h-5" />
                        <span>Buka Shift</span>
                    </button>
                    @endunless

                    <button
                        onclick="window.open('{{ route('pos.display') }}', 'CustomerDisplay', 'width=1280,height=800,menubar=no,toolbar=no,location=no,status=no'); showMobileMenu = false"
                        class="w-full flex items-center gap-3 px-4 py-3.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl transition-colors font-medium border border-indigo-100"
                    >
                        <x-lucide name="monitor" class="w-5 h-5" />
                        <span>Buka Layar Pelanggan</span>
                    </button>

                    <button 
                        wire:click="openPendingModal(); showMobileMenu = false" 
                        class="w-full flex items-center justify-between px-4 py-3.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl transition-colors font-medium border border-amber-100"
                    >
                        <div class="flex items-center gap-3">
                            <x-lucide name="clock" class="w-5 h-5 text-amber-600" />
                            <span>Pesanan Pending</span>
                        </div>
                        <span class="bg-amber-200 text-amber-800 text-xs py-0.5 px-2.5 rounded-full font-bold">
                            {{ count($this->pendingCarts) }}
                        </span>
                    </button>

                    @can('view_self_orders')
                    <button 
                        @click="showSelfOrderModal = true; showMobileMenu = false" 
                        class="w-full flex items-center justify-between px-4 py-3.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl transition-colors font-medium border border-emerald-100"
                    >
                        <div class="flex items-center gap-3">
                            <x-lucide name="smartphone" class="w-5 h-5 text-emerald-600" />
                            <span>Pesanan Mandiri (QR)</span>
                        </div>
                        <span class="bg-emerald-600 text-white text-xs py-0.5 px-2.5 rounded-full font-bold">
                            {{ $this->selfOrdersCount }}
                        </span>
                    </button>
                    @endcan

                    <button 
                        wire:click="openHistoryModal(); showMobileMenu = false" 
                        class="w-full flex items-center justify-between px-4 py-3.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl transition-colors font-medium border border-blue-100"
                    >
                        <div class="flex items-center gap-3">
                            <x-lucide name="history" class="w-5 h-5" />
                            <span>Riwayat Transaksi</span>
                        </div>
                        <span class="bg-blue-200 text-blue-800 text-xs py-0.5 px-2.5 rounded-full font-bold">
                            {{ $this->todayTransactions->count() }}
                        </span>
                    </button>

                    <button 
                        wire:click="openReturnHistoryModal(); showMobileMenu = false" 
                        class="w-full flex items-center justify-between px-4 py-3.5 bg-orange-50 hover:bg-orange-100 text-orange-700 rounded-xl transition-colors font-medium border border-orange-100"
                    >
                        <div class="flex items-center gap-3">
                            <x-lucide name="list" class="w-5 h-5" />
                            <span>Riwayat Retur</span>
                        </div>
                        <span class="bg-orange-200 text-orange-800 text-xs py-0.5 px-2.5 rounded-full font-bold">
                            {{ $this->todayReturns->count() }}
                        </span>
                    </button>

                    <button 
                        wire:click="openReturnModal(); showMobileMenu = false" 
                        class="w-full flex items-center gap-3 px-4 py-3.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition-colors font-semibold border border-red-100"
                    >
                        <x-lucide name="rotate-ccw" class="w-5 h-5" />
                        <span>Retur Penjualan</span>
                    </button>

                    @if($this->todayShift && $this->todayShift->status === 'open')
                        <button 
                            wire:click="openCloseShiftModal(); showMobileMenu = false" 
                            class="w-full flex items-center gap-3 px-4 py-3.5 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-colors font-semibold shadow-lg shadow-red-600/20"
                        >
                            <x-lucide name="power" class="w-5 h-5" />
                            <span>Tutup Shift Kasir</span>
                        </button>
                    @endif

                    <a 
                        href="{{ route('admin.dashboard') }}" 
                        class="w-full flex items-center gap-3 px-4 py-3.5 bg-gray-50 hover:bg-gray-100 text-gray-700 rounded-xl transition-colors font-medium border border-gray-200"
                    >
                        <x-lucide name="layout-dashboard" class="w-5 h-5" />
                        <span>Kembali ke Dashboard</span>
                    </a>
                </div>
            </div>
        </div>

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
                <x-lucide name="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" />
            </div>

            <!-- Categories -->
            <div class="flex gap-2 overflow-x-auto pb-2 custom-scroll">
                <button 
                    wire:click="selectCategory(null)"
                    class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all flex items-center gap-2 {{ !$selectedCategoryId ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    <x-lucide name="grid-3x3" class="w-4 h-4" />
                    Semua
                </button>
                @foreach($this->categories as $category)
                    <button 
                        wire:click="selectCategory({{ $category->id }})"
                        class="px-4 py-2 rounded-lg font-medium whitespace-nowrap transition-all flex items-center gap-2 {{ $selectedCategoryId === $category->id ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                    >
                        <x-lucide name="tag" class="w-4 h-4" />
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
                        $isBomProduct = $product->hasBom();

                        // Dihitung sekali untuk seluruh grid lewat productAvailability()
                        // (satu query batch), bukan per produk di sini — lihat
                        // PosCheckout::productAvailability().
                        $availabilityRow = $this->productAvailability[$product->id] ?? null;
                        $bomQty = $isBomProduct ? ($availabilityRow['bomQty'] ?? 0) : null;
                        $subQty = $isBomProduct ? ($availabilityRow['subQty'] ?? 0) : 0;
                        $hasSubstituteOption = $subQty > 0;

                        $isOutOfStock = $isBomProduct
                            ? $bomQty <= 0
                            : ($product->track_stock && $product->stock <= 0);
                    @endphp
                    <div
                        @if(!$isOutOfStock)
                            @if($hasModifiers)
                                @click="openModifierModal({{ json_encode($productData) }})"
                            @else
                                wire:click.throttle.300ms="addToCart({{ $product->id }}, [])"
                            @endif
                        @endif
                        wire:key="product-{{ $product->id }}-{{ $bomQty ?? 'x' }}-{{ $subQty }}"
                        class="bg-white rounded-xl p-4 shadow-sm hover:shadow-md transition-all cursor-pointer group border border-gray-100 hover:border-primary-300 relative {{ $isOutOfStock ? 'opacity-60 grayscale cursor-not-allowed hover:border-gray-200 hover:shadow-none' : '' }}"
                    >
                        <!-- Badges -->
                        <div class="absolute top-2 right-2 flex flex-col gap-1 items-end z-10">
                            @if($isBomProduct)
                                @if($bomQty > 0)
                                    <div class="bg-emerald-600 text-white text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                                        <x-lucide name="box" class="w-3 h-3" />
                                        <span>Stok: {{ $bomQty }}</span>
                                    </div>
                                @else
                                    <div class="bg-red-600 text-white text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                                        <x-lucide name="box" class="w-3 h-3" />
                                        <span>Habis</span>
                                    </div>
                                @endif
                            @elseif($product->track_stock)
                                @if($product->stock > 0)
                                    <div class="bg-emerald-600 text-white text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                                        <x-lucide name="box" class="w-3 h-3" />
                                        <span>Stok: {{ $product->stock }}</span>
                                    </div>
                                @else
                                    <div class="bg-red-600 text-white text-[11px] px-2.5 py-0.5 rounded-full font-bold shadow-sm flex items-center gap-1">
                                        <x-lucide name="box" class="w-3 h-3" />
                                        <span>Habis</span>
                                    </div>
                                @endif
                            @endif

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
                                <x-lucide name="package" class="w-8 h-8 text-primary-500" />
                            </div>
                        @endif
                        
                        <!-- Out of Stock Overlay -->
                        @if($isOutOfStock)
                            <div class="absolute inset-0 bg-white/60 z-[5] rounded-xl flex items-center justify-center backdrop-blur-[1px]">
                                <div class="bg-red-600 text-white px-4 py-1.5 rounded-xl font-black text-sm shadow-lg transform -rotate-12 border-2 border-white uppercase tracking-wider">
                                    STOK HABIS
                                </div>
                            </div>
                        @endif

                        <h3 class="font-medium text-gray-800 group-hover:text-primary-600 transition-colors line-clamp-2">
                            {{ $product->name }}
                        </h3>
                        <p class="text-primary-600 font-bold mt-1">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>

                        @if($isBomProduct)
                            @if($bomQty <= 0)
                                <p class="text-xs mt-1.5 font-bold text-red-600 flex items-center gap-1 bg-red-50 border border-red-100 px-2 py-1 rounded-md">
                                    <x-lucide name="box" class="w-3.5 h-3.5" />
                                    <span>Stok normal: 0</span>
                                </p>

                                {{-- Komponen yang menjadi penyebab habis --}}
                                @foreach($product->bom as $bomLine)
                                    @if($bomLine->component && $bomLine->component->stock < $bomLine->quantity)
                                        <p class="text-[11px] mt-1 text-amber-700 flex items-center gap-1">
                                            <x-lucide name="triangle-alert" class="w-3 h-3" />
                                            <span>{{ $bomLine->component->name }} habis</span>
                                        </p>
                                    @endif
                                @endforeach

                                @if($hasSubstituteOption)
                                    <button type="button"
                                        wire:click.stop="openSubstitutionModal({{ $product->id }})"
                                        class="relative z-[6] mt-1.5 w-full text-[11px] font-bold text-amber-900 bg-amber-300 hover:bg-amber-400 border border-amber-500 px-2 py-1.5 rounded-md flex items-center justify-center gap-1 transition-colors">
                                        <x-lucide name="repeat" class="w-3.5 h-3.5" />
                                        <span>Gunakan substitusi ({{ $subQty }})</span>
                                    </button>
                                @endif
                            @else
                                <p class="text-xs mt-1.5 font-semibold text-emerald-700 flex items-center gap-1 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md">
                                    <x-lucide name="box" class="w-3.5 h-3.5" />
                                    <span>Stok: {{ $bomQty }}</span>
                                </p>
                            @endif

                            {{-- Komposisi komponen: popover agar kartu tidak jadi ramai --}}
                            <div class="relative mt-1" x-data="{ openBom: false }" @click.stop>
                                <button type="button" @click="openBom = !openBom"
                                    class="text-[11px] text-gray-500 hover:text-primary-600 inline-flex items-center gap-1">
                                    <x-lucide name="list" class="w-3 h-3" />
                                    <span>Komponen ({{ $product->bom->count() }})</span>
                                </button>
                                <div x-show="openBom" x-cloak @click.outside="openBom = false" x-transition
                                    class="absolute bottom-full left-0 mb-1 w-56 bg-white border border-gray-200 rounded-lg shadow-lg p-2 z-30">
                                    <p class="text-[11px] font-bold text-gray-700 mb-1">Komposisi per 1 porsi</p>
                                    <ul class="space-y-0.5">
                                        @foreach($product->bom as $bomLine)
                                            <li class="text-[11px] flex justify-between gap-2 {{ $bomLine->component && $bomLine->component->stock < $bomLine->quantity ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                <span>{{ rtrim(rtrim(number_format($bomLine->quantity, 2, ',', '.'), '0'), ',') }} × {{ $bomLine->component?->name ?? '-' }}</span>
                                                <span class="text-gray-400 whitespace-nowrap">sisa {{ rtrim(rtrim(number_format($bomLine->component?->stock ?? 0, 2, ',', '.'), '0'), ',') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                    <p class="text-[11px] text-gray-500 mt-1.5 pt-1.5 border-t border-gray-100">
                                        Estimasi stok: <span class="font-bold text-gray-700">{{ $bomQty }}</span>
                                    </p>
                                </div>
                            </div>
                        @elseif($product->track_stock)
                            @if($product->stock <= 0)
                                <p class="text-xs mt-1.5 font-bold text-red-600 flex items-center gap-1 bg-red-50 border border-red-100 px-2 py-1 rounded-md">
                                    <x-lucide name="box" class="w-3.5 h-3.5" />
                                    <span>Stok Habis (0)</span>
                                </p>
                            @else
                                <p class="text-xs mt-1.5 font-semibold text-emerald-700 flex items-center gap-1 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded-md">
                                    <x-lucide name="box" class="w-3.5 h-3.5" />
                                    <span>Stok: {{ $product->stock }}</span>
                                </p>
                            @endif
                        @else
                            <p class="text-xs mt-1.5 text-gray-400 flex items-center gap-1">
                                <x-lucide name="box" class="w-3 h-3" />
                                <span>Tanpa Batas Stok</span>
                            </p>
                        @endif
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 text-gray-500">
                        <x-lucide name="inbox" class="w-16 h-16 mx-auto mb-4 text-gray-300" />
                        <p>Tidak ada produk ditemukan</p>
                        <p class="text-sm mt-2">Pastikan ada produk aktif di database</p>
                    </div>
                @endforelse
            </div>
            
            <!-- Load More -->
            @if($this->totalProductsCount > count($this->products))
                <div class="mt-6 text-center">
                    <button 
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="px-6 py-2 bg-white border border-gray-200 text-primary-600 font-medium rounded-full shadow-sm hover:bg-gray-50 hover:border-primary-200 transition-all flex items-center gap-2 mx-auto disabled:opacity-50"
                    >
                        <x-lucide name="arrow-down-circle" wire:loading.remove wire:target="loadMore" class="w-4 h-4" />
                        <svg wire:loading wire:target="loadMore" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Muat Lebih Banyak</span>
                        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded-full ml-1">{{ count($this->products) }} / {{ $this->totalProductsCount }}</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Substitution Modal (Livewire-driven: butuh state server & validasi stok) -->
        @if($showSubstitutionModal)
            <div class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4"
                 wire:click.self="closeSubstitutionModal"
                 @keydown.escape.window="$wire.closeSubstitutionModal()">
                <div class="bg-white rounded-2xl w-full max-w-lg shadow-2xl max-h-[90vh] flex flex-col">
                    <div class="p-5 border-b flex justify-between items-start flex-none">
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Ganti Komponen</h3>
                            <p class="text-sm text-gray-500">{{ $substitutionProductName }} — komposisi normal tidak mencukupi</p>
                        </div>
                        <button type="button" wire:click="closeSubstitutionModal" class="text-gray-400 hover:text-gray-600 p-1.5 hover:bg-gray-100 rounded-lg">
                            <x-lucide name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-5 overflow-y-auto flex-1 custom-scroll space-y-4">
                        @forelse($substitutionLines as $line)
                            <div class="border border-gray-200 rounded-xl p-3">
                                <div class="flex items-center gap-2 mb-2">
                                    <x-lucide name="triangle-alert" class="w-4 h-4 text-amber-500" />
                                    <p class="text-sm font-semibold text-gray-800">
                                        {{ $line['component_name'] }}
                                        <span class="font-normal text-gray-500">
                                            — butuh {{ $line['needed'] }} {{ $line['unit'] }}, tersisa {{ $line['available'] }}
                                        </span>
                                    </p>
                                </div>

                                @forelse($line['options'] as $option)
                                    @php
                                        $isChosen = ($selectedSubstitutions[$line['bom_id']]['rule_id'] ?? null) === $option['rule_id'];
                                    @endphp
                                    <button type="button"
                                        @disabled(!$option['is_enough'])
                                        wire:click="chooseSubstitution({{ $line['bom_id'] }}, {{ $option['rule_id'] }})"
                                        class="w-full text-left mb-2 px-3 py-2 rounded-lg border transition-all flex items-center justify-between gap-2
                                            {{ $isChosen ? 'border-primary-500 bg-primary-50 ring-1 ring-primary-500' : 'border-gray-200 hover:bg-gray-50' }}
                                            {{ !$option['is_enough'] ? 'opacity-50 cursor-not-allowed' : '' }}">
                                        <span class="text-sm font-medium text-gray-800">
                                            Gunakan {{ rtrim(rtrim(number_format($option['quantity'], 2, ',', '.'), '0'), ',') }}
                                            {{ $option['unit'] }} {{ $option['component_name'] }}
                                        </span>
                                        <span class="text-xs {{ $option['is_enough'] ? 'text-emerald-600' : 'text-red-600' }} whitespace-nowrap">
                                            sisa {{ rtrim(rtrim(number_format($option['available'], 2, ',', '.'), '0'), ',') }}
                                        </span>
                                    </button>
                                @empty
                                    <p class="text-xs text-gray-400 italic">Tidak ada aturan pengganti untuk komponen ini.</p>
                                @endforelse

                                {{-- Substitusi manual (butuh permission khusus) --}}
                                @can('apply_manual_substitution')
                                    <div x-data="{ open: false, componentId: '', qty: 1 }" class="mt-2 pt-2 border-t border-gray-100">
                                        <button type="button" @click="open = !open" class="text-xs font-semibold text-primary-600 hover:text-primary-700 inline-flex items-center gap-1">
                                            <x-lucide name="settings-2" class="w-3.5 h-3.5" />
                                            <span>Ganti manual</span>
                                        </button>
                                        <div x-show="open" x-cloak class="mt-2 flex gap-2 items-end">
                                            <select x-model="componentId" class="flex-1 text-xs border border-gray-200 rounded-lg p-2">
                                                <option value="">-- Pilih komponen --</option>
                                                @foreach($this->substitutableComponents as $comp)
                                                    <option value="{{ $comp->id }}">{{ $comp->name }} (sisa {{ rtrim(rtrim(number_format($comp->stock, 2, ',', '.'), '0'), ',') }})</option>
                                                @endforeach
                                            </select>
                                            <input type="number" step="0.01" min="0.01" x-model="qty" class="w-20 text-xs border border-gray-200 rounded-lg p-2" placeholder="Qty">
                                            <button type="button"
                                                @click="$wire.chooseManualSubstitution({{ $line['bom_id'] }}, parseInt(componentId), parseFloat(qty)); open = false"
                                                class="px-3 py-2 text-xs font-semibold text-white bg-primary-600 hover:bg-primary-700 rounded-lg">
                                                Pakai
                                            </button>
                                        </div>
                                    </div>
                                @endcan

                                @if(isset($selectedSubstitutions[$line['bom_id']]) && !($selectedSubstitutions[$line['bom_id']]['rule_id'] ?? null))
                                    <p class="text-xs text-primary-700 mt-2 font-medium">
                                        Pengganti manual dipilih ({{ $selectedSubstitutions[$line['bom_id']]['quantity'] }} unit).
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Tidak ada komponen yang perlu diganti.</p>
                        @endforelse
                    </div>

                    <div class="p-5 border-t bg-gray-50 flex gap-3 flex-none rounded-b-2xl">
                        <button type="button" wire:click="closeSubstitutionModal" class="flex-1 py-2.5 bg-white border border-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-50">
                            Batal
                        </button>
                        <button type="button" wire:click="applySubstitutionAndAdd" class="flex-1 py-2.5 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg">
                            Tambah ke Keranjang
                        </button>
                    </div>
                </div>
            </div>
        @endif

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
                                    <div 
                                        :class="isModifierSelected(group.id, modifier.id) ? 'border-primary-500 bg-primary-50 text-primary-700' : 'border-gray-200 bg-white hover:bg-gray-50'"
                                        class="w-full flex items-center justify-between px-4 py-3 border rounded-lg transition-all"
                                    >
                                        <div class="flex items-center gap-3 cursor-pointer flex-1" @click="selectModifier(group.id, modifier, group.selection_type)">
                                            <span 
                                                :class="isModifierSelected(group.id, modifier.id) ? 'bg-primary-500' : 'border-2 border-gray-300 bg-white'"
                                                class="w-5 h-5 rounded-full flex items-center justify-center transition-all flex-shrink-0"
                                            >
                                                <svg x-show="isModifierSelected(group.id, modifier.id)" class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span class="font-medium" x-text="modifier.name"></span>
                                        </div>
                                        
                                        <div class="flex items-center gap-3">
                                            <span :class="modifier.price_adjustment > 0 ? 'text-primary-600' : 'text-gray-400'" class="text-sm font-medium">
                                                <template x-if="Number(modifier.price_adjustment) > 0">
                                                    <span>+Rp <span x-text="formatRupiah(modifier.price_adjustment)"></span></span>
                                                </template>
                                                <template x-if="modifier.price_adjustment == 0">
                                                    <span>Gratis</span>
                                                </template>
                                            </span>

                                            <!-- Qty selector if selected (only for multi-selection modifier groups) -->
                                            <template x-if="isModifierSelected(group.id, modifier.id) && group.selection_type !== 'single'">
                                                <div class="flex items-center gap-1 bg-white border border-gray-200 rounded-lg p-0.5 shadow-xs">
                                                    <button type="button" @click.stop="changeModifierQty(group.id, modifier.id, -1)" class="w-6 h-6 rounded flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs">
                                                        -
                                                    </button>
                                                    <span class="w-5 text-center font-bold text-xs" x-text="getModifierQty(group.id, modifier.id)"></span>
                                                    <button type="button" @click.stop="changeModifierQty(group.id, modifier.id, 1)" class="w-6 h-6 rounded flex items-center justify-center bg-primary-100 hover:bg-primary-200 text-primary-700 font-bold text-xs">
                                                        +
                                                    </button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
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
            <span><kbd class="px-2 py-1 bg-gray-700 rounded">Esc</kbd> Tutup Popup</span>
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
                    <x-lucide name="shopping-cart" class="w-5 h-5" />
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
                        <x-lucide name="arrow-left" class="w-6 h-6" />
                    </button>
                    <x-lucide name="shopping-cart" class="w-5 h-5" />
                    Keranjang
                </h2>
                <div class="flex items-center gap-2">
                    @if(count($cart) > 0)
                        <button wire:click="holdCart" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-lg transition-colors border border-amber-200 flex items-center gap-1 text-xs font-bold" title="Tunda pesanan ini">
                            <x-lucide name="clock" class="w-3.5 h-3.5" />
                            <span>Pending</span>
                        </button>

                        <button wire:click="clearCart" class="text-red-500 hover:text-red-700 text-sm font-medium flex items-center gap-1">
                            <x-lucide name="trash-2" class="w-4 h-4" />
                            Hapus
                        </button>
                    @endif
                </div>
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
                                        {{ $mod['name'] }}@if(($mod['qty'] ?? 1) > 1) ×{{ $mod['qty'] }}@endif @if(!$loop->last), @endif
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
                            <x-lucide name="x" class="w-5 h-5" />
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
                                <x-lucide name="minus" class="w-4 h-4" />
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
                                <x-lucide name="plus" class="w-4 h-4" />
                            </button>
                        </div>
                        <span class="font-bold text-gray-800">
                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-400">
                    <x-lucide name="shopping-bag" class="w-16 h-16 mx-auto mb-4" />
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

            @if($qrisOrderId && $qrisCodeUrl && !$showQrisModal)
                <button 
                    wire:click="reopenQris"
                    type="button"
                    class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold rounded-xl transition-all shadow-md shadow-blue-600/30 flex items-center justify-center gap-2 animate-pulse cursor-pointer"
                >
                    <x-lucide name="qr-code" class="w-5 h-5" />
                    <span>Buka Kembali QRIS (Aktif)</span>
                </button>
            @endif

            <button 
                wire:click="openPaymentModal"
                @if(count($cart) === 0) disabled @endif
                class="w-full py-4 bg-primary-600 hover:bg-primary-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
            >
                <x-lucide name="credit-card" class="w-5 h-5" />
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
                            <x-lucide name="wallet" class="w-6 h-6" />
                            Pembayaran
                        </h3>
                        <button wire:click="closePaymentModal" class="text-gray-400 hover:text-gray-600 p-1">
                            <x-lucide name="x" class="w-6 h-6" />
                        </button>
                    </div>
                </div>

                <div class="p-6 space-y-6 overflow-y-auto flex-1 custom-scroll">
                    <!-- Active QRIS Alert Banner -->
                    @if($qrisOrderId && $qrisCodeUrl)
                        <div class="bg-blue-50 border border-blue-200 rounded-xl p-3.5 flex items-center justify-between shadow-sm">
                            <div class="flex items-center gap-2.5 text-xs text-blue-900 font-medium">
                                <x-lucide name="info" class="w-5 h-5 text-blue-600 flex-shrink-0" />
                                <span>Ada QRIS aktif yang sedang menunggu pembayaran.</span>
                            </div>
                            <button 
                                type="button"
                                wire:click="reopenQris"
                                class="px-3.5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <x-lucide name="qr-code" class="w-4 h-4" />
                                <span>Tampilkan</span>
                            </button>
                        </div>
                    @endif
                    <!-- Payment Method -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($this->paymentSources as $source)
                                <button 
                                    wire:click="$set('paymentSourceId', {{ $source->id }})"
                                    class="p-4 rounded-2xl border-2 text-center transition-all flex items-center justify-between gap-2 {{ $paymentSourceId === $source->id ? 'border-primary-500 bg-primary-50 text-primary-700 font-bold ring-2 ring-primary-500/20 shadow-sm' : 'border-gray-200 hover:border-gray-300 text-gray-700' }}"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-xl flex items-center justify-center {{ $source->type === 'qris' ? 'bg-indigo-100 text-indigo-600' : 'bg-green-100 text-green-600' }}">
                                            <x-lucide name="{{ $source->type === 'qris' ? 'qr-code' : 'banknotes' }}" class="w-4 h-4" />
                                        </div>
                                        <span class="font-bold text-base">{{ $source->name }}</span>
                                    </div>
                                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $source->type === 'qris' ? 'bg-indigo-100 text-indigo-700 border border-indigo-200' : 'bg-green-100 text-green-700 border border-green-200' }}">
                                        {{ $source->type === 'qris' ? 'Scan QR' : 'Bayar Tunai' }}
                                    </span>
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
                                        <x-lucide name="alert-circle" class="w-5 h-5" />
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


                    <!-- Order Type & Service Area -->
                    <div class="mb-6 space-y-4">
                        <!-- Order Type Toggle -->
                        <div class="bg-gray-100 p-1 rounded-xl flex">
                            <button 
                                wire:click="$set('orderType', 'dine_in')"
                                class="flex-1 py-2 rounded-lg text-sm font-medium transition-all {{ $orderType === 'dine_in' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                            >
                                Makan di Tempat
                            </button>
                            <button 
                                wire:click="$set('orderType', 'take_away')"
                                class="flex-1 py-2 rounded-lg text-sm font-medium transition-all {{ $orderType === 'take_away' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-500 hover:text-gray-700' }}"
                            >
                                Bawa Pulang
                            </button>
                        </div>

                        <!-- Service Area Grid (Only for Dine In) -->
                        @if($orderType === 'dine_in')
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Meja / Area (opsional jika isi Nomor Pager)</label>
                                @if($this->serviceAreas->count() > 0)
                                    <div class="grid grid-cols-4 gap-2 max-h-[200px] overflow-y-auto custom-scroll p-1">
                                        @foreach($this->serviceAreas as $area)
                                            <button
                                                wire:click="$set('selectedServiceAreaId', {{ $area->id }})"
                                                class="p-2 rounded-lg border text-sm font-medium transition-all relative overflow-hidden group
                                                    {{ $selectedServiceAreaId === $area->id
                                                        ? 'border-primary-500 bg-primary-50 text-primary-700 ring-1 ring-primary-500'
                                                        : 'border-gray-200 hover:border-primary-300 hover:bg-gray-50 text-gray-700'
                                                    }}"
                                            >
                                                <span class="block truncate">{{ $area->name }}</span>
                                                <span class="text-xs text-gray-400">{{ $area->code }}</span>

                                                @if($selectedServiceAreaId === $area->id)
                                                    <div class="absolute inset-0 border-2 border-primary-500 rounded-lg pointer-events-none"></div>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                    @if(!$selectedServiceAreaId && !$selectedPagerId)
                                        <p class="text-xs text-red-500 mt-1">* Wajib pilih Meja/Area atau isi Nomor Pager untuk Makan di Tempat</p>
                                    @endif
                                @else
                                    <div class="text-center py-4 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                        <p class="text-sm text-gray-500">Belum ada data area</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Nomor Pager (opsional, POS & Take Away) -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Nomor Pager (opsional)</label>
                            @if($this->pagers->count() > 0)
                                <select wire:model.live="selectedPagerId" class="w-full px-4 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                                    <option value="">-- Tanpa Pager --</option>
                                    @foreach($this->pagers as $pager)
                                        <option value="{{ $pager->id }}">
                                            Pager {{ $pager->number }}{{ $pager->docking_number ? " (Docking {$pager->docking_number})" : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="text-center py-3 bg-gray-50 rounded-lg border border-dashed border-gray-300">
                                    <p class="text-sm text-gray-500">Belum ada data pager</p>
                                </div>
                            @endif
                        </div>
                    </div>

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

                    <!-- Customer Phone & Email (opsional, buat kirim struk digital) -->
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">No. HP (opsional)</label>
                            <input
                                type="text"
                                wire:model="customerPhone"
                                placeholder="08xxxxxxxxxx"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Email (opsional)</label>
                            <input
                                type="email"
                                wire:model="customerEmail"
                                placeholder="email@contoh.com"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500"
                            >
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 -mt-2">Isi kalau ingin struk dikirim ke pelanggan lewat WhatsApp/email.</p>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catatan Pesanan (opsional)</label>
                        <textarea 
                            wire:model="notes"
                            placeholder="Masukkan catatan (misal: Bakso A tidak pakai sayur, dll)..."
                            rows="2"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500"
                        ></textarea>
                    </div>
                </div>

                <div class="p-6 border-t bg-gray-50 rounded-b-2xl flex-none">
                    <button
                        wire:click.debounce.500ms="processPayment"
                        wire:loading.attr="disabled"
                        wire:target="processPayment"
                        class="w-full py-4 bg-green-600 hover:bg-green-700 disabled:bg-gray-300 text-white font-bold rounded-xl transition-colors flex items-center justify-center gap-2"
                    >
                        <span wire:loading.remove wire:target="processPayment">
                            <x-lucide name="check-circle" class="w-5 h-5 inline mr-2" />
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

    <!-- QRIS Payment Modal (Real-time SSE Listener) -->
    @if($showQrisModal)
        <div
            wire:key="qris-modal-{{ $qrisOrderId ?? 'none' }}"
            @qris-start-sse.window="init()"
            x-data="{
                eventSource: null,
                qrisExpiresAtMs: @js($qrisExpiresAtMs),
                qrisExpiresIn: @js($qrisExpiresIn),
                qrisOrderId: @js($qrisOrderId),
                countdown: null,
                timer: null,
                posChannel: null,
                isChecking: false,
                showCancelConfirm: false,
                activeTimerOrderId: null,

                init() {
                    this.startCountdown();
                    this.startSse();
                    this.startChannel();

                    this.$watch('qrisOrderId', (newVal, oldVal) => {
                        if (newVal && newVal !== oldVal) {
                            this.startCountdown();
                            this.startSse();
                        }
                    });
                    this.$watch('qrisExpiresAtMs', (newVal) => {
                        if (newVal) {
                            this.startCountdown();
                        }
                    });
                },
                startChannel() {
                    try {
                        this.posChannel = new BroadcastChannel('pos_channel_' + {{ auth()->id() }});
                        this.posChannel.onmessage = (e) => {
                            if (e.data && e.data.type === 'qris_paid' && e.data.transaction_id) {
                                this.cleanup();
                                $wire.onQrisPaid(e.data.transaction_id);
                            }
                        };
                    } catch (err) { console.log(err); }
                },
                startCountdown() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = null;
                    this.activeTimerOrderId = this.qrisOrderId;

                    const tick = () => {
                        const target = (this.qrisExpiresAtMs && this.qrisExpiresAtMs > 1000000000000)
                            ? this.qrisExpiresAtMs
                            : (Date.now() + (this.qrisExpiresIn || 300) * 1000);

                        this.countdown = Math.max(0, Math.round((target - Date.now()) / 1000));
                        if (this.countdown <= 0) {
                            if (this.timer) clearInterval(this.timer);
                            this.timer = null;
                            $wire.onQrisExpired();
                        }
                    };
                    tick();
                    this.timer = setInterval(() => tick(), 1000);
                },
                startSse() {
                    if (!'{{ $qrisOrderId }}') return;
                    if (this.eventSource) this.eventSource.close();
                    
                    const url = '{{ route('payment.qris.sse', ':orderId') }}'.replace(':orderId', '{{ $qrisOrderId }}');
                    this.eventSource = new EventSource(url);

                    this.eventSource.addEventListener('paid', (e) => {
                        const data = JSON.parse(e.data);
                        this.cleanup();
                        $wire.onQrisPaid(data.transaction_id);
                    });

                    this.eventSource.addEventListener('expired', (e) => {
                        this.cleanup();
                        $wire.onQrisExpired();
                    });

                    this.eventSource.addEventListener('failed', (e) => {
                        this.cleanup();
                        $wire.dispatch('notify', { type: 'error', message: 'Pembayaran gagal atau dibatalkan.' });
                    });

                    this.eventSource.onerror = (err) => {
                        console.log('SSE connection status event/retry active.');
                    };
                },
                manualCheck() {
                    this.isChecking = true;
                    $wire.checkQrisStatus(false).then(() => {
                        this.isChecking = false;
                        if (!this.timer && this.countdown > 0) {
                            this.startCountdown();
                        }
                    });
                },
                cleanup() {
                    if (this.eventSource) {
                        this.eventSource.close();
                        this.eventSource = null;
                    }
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    if (this.posChannel) {
                        this.posChannel.close();
                        this.posChannel = null;
                    }
                },
                formatTime(seconds) {
                    if (seconds === null || seconds === undefined) {
                        const target = (this.qrisExpiresAtMs && this.qrisExpiresAtMs > 1000000000000)
                            ? this.qrisExpiresAtMs
                            : (Date.now() + (this.qrisExpiresIn || 300) * 1000);
                        seconds = Math.max(0, Math.round((target - Date.now()) / 1000));
                    }
                    if (seconds <= 0) return '00:00';
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return `${m}:${s < 10 ? '0' : ''}${s}`;
                }
            }"
            @unmount="cleanup()"
            class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl w-full max-w-md shadow-xl overflow-hidden border border-gray-100 relative">
                <!-- Custom Security Cancel Confirmation Modal Overlay -->
                <div 
                    x-show="showCancelConfirm" 
                    x-cloak 
                    class="absolute inset-0 z-30 flex items-center justify-center p-6 bg-slate-900/85 backdrop-blur-md"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="bg-white rounded-3xl p-6 text-center shadow-2xl border border-gray-100 space-y-4 w-full animate-fade-in">
                        <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
                            <x-lucide name="alert-triangle" class="w-7 h-7" />
                        </div>
                        
                        <div class="space-y-1">
                            <h3 class="text-lg font-extrabold text-gray-900">Batalkan QRIS Ini?</h3>
                            <p class="text-xs text-gray-500 leading-relaxed">
                                Transaksi QRIS ini akan dibatalkan. Kode QRIS di layar pelanggan juga akan otomatis ditutup.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-2">
                            <button 
                                @click="showCancelConfirm = false"
                                class="py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-2xl text-xs transition-all cursor-pointer"
                            >
                                Kembali
                            </button>
                            <button 
                                @click="showCancelConfirm = false; cleanup(); $wire.cancelQris()"
                                class="py-3 bg-red-600 hover:bg-red-700 text-white font-bold rounded-2xl text-xs transition-all shadow-md shadow-red-600/30 flex items-center justify-center gap-1.5 cursor-pointer"
                            >
                                <x-lucide name="trash-2" class="w-4 h-4" />
                                <span>Ya, Batalkan</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Header -->
                <div class="bg-primary-600 p-5 text-white text-center relative">
                    <button
                        @click="cleanup(); $wire.hideQrisModal()"
                        class="absolute top-3.5 right-3.5 text-white/70 hover:text-white p-1.5 rounded-lg hover:bg-white/10 transition-colors"
                        title="Sembunyikan QRIS (Tetap Aktif)"
                    >
                        <x-lucide name="x" class="w-5 h-5" />
                    </button>
                    <h3 class="text-base font-bold">Pembayaran QRIS</h3>
                    <p class="text-xs text-white/70 mt-0.5">Scan dengan aplikasi e-Wallet atau m-Banking</p>
                </div>

                <!-- Body -->
                <div class="p-6 text-center space-y-5">
                    <!-- Total Bill -->
                    <div>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide block">Total Pembayaran</span>
                        <span class="text-2xl font-extrabold text-gray-900">Rp {{ number_format($qrisAmount ?: $this->total, 0, ',', '.') }}</span>
                        <span class="text-[11px] text-gray-400 block mt-0.5">Inv: {{ $qrisInvoiceNumber }}</span>
                    </div>

                    <!-- QR Code Image & Loader -->
                    <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm mx-auto w-fit">
                        @if($qrisCodeUrl)
                            <img src="{{ $qrisCodeUrl }}" alt="QRIS Code" class="w-52 h-52 mx-auto object-contain rounded-lg">
                        @else
                            <div class="w-52 h-52 flex items-center justify-center bg-gray-50 rounded-lg">
                                <div class="animate-spin w-7 h-7 border-[3px] border-gray-200 border-t-primary-600 rounded-full"></div>
                            </div>
                        @endif
                    </div>

                    <!-- Live Status -->
                    <div class="inline-flex items-center gap-1.5 bg-blue-50 text-primary-600 text-xs font-semibold px-3 py-1.5 rounded-full">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-600"></span>
                        <span>Menunggu pembayaran</span>
                    </div>

                    <!-- Countdown Timer -->
                    <div class="text-xs text-gray-500">
                        <span>Berlaku hingga </span>
                        <span class="font-mono font-bold text-gray-900 text-sm" x-text="formatTime(countdown)"></span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2">
                        <!-- Manual Check Status Button (Case 3) -->
                        <button
                            @click="manualCheck()"
                            :disabled="isChecking"
                            class="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-60 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-2 transition-all cursor-pointer"
                        >
                            <span x-show="!isChecking" class="flex items-center gap-1.5">
                                <x-lucide name="refresh-cw" class="w-4 h-4" />
                                <span>Cek Status Pembayaran</span>
                            </span>
                            <span x-show="isChecking" x-cloak class="flex items-center gap-1.5">
                                <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                                <span>Mengecek...</span>
                            </span>
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <!-- Hide Modal (Keep Active) -->
                            <button
                                @click="cleanup(); $wire.hideQrisModal()"
                                class="py-2.5 border border-gray-200 hover:bg-gray-50 text-gray-600 font-semibold rounded-xl text-xs transition-colors cursor-pointer"
                            >
                                Sembunyikan
                            </button>

                            <!-- Cancel / Change Method Button with Security Validation -->
                            <button
                                @click="showCancelConfirm = true"
                                class="py-2.5 border border-red-200 hover:bg-red-50 text-red-600 font-semibold rounded-xl text-xs transition-colors cursor-pointer"
                            >
                                Batalkan QRIS
                            </button>
                        </div>

                        <!-- Regenerate QRIS (Case 5) -->
                        <button
                            x-show="countdown <= 0"
                            x-cloak
                            @click="cleanup(); $wire.regenerateQris()"
                            wire:loading.attr="disabled"
                            wire:target="regenerateQris"
                            class="w-full py-3 bg-amber-500 hover:bg-amber-600 disabled:opacity-60 text-white font-bold rounded-xl text-xs flex items-center justify-center gap-1.5 transition-colors cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="regenerateQris" class="flex items-center gap-1.5">
                                <x-lucide name="rotate-cw" class="w-4 h-4" />
                                <span>Buat QRIS Baru</span>
                            </span>
                            <span wire:loading wire:target="regenerateQris" class="flex items-center gap-1.5">
                                <div class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"></div>
                                <span>Membuat QRIS Baru...</span>
                            </span>
                        </button>
                    </div>
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
                            <x-lucide name="clock" class="w-6 h-6" />
                            Tutup Shift
                        </h3>
                        <button wire:click="$set('showCloseShiftModal', false)" class="text-gray-400 hover:text-gray-600">
                            <x-lucide name="x" class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                    <!-- Opening Cash -->
                    <div x-data="moneyInput({{ $openingCash }}, 'openingCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Modal Awal (Cash)</label>
                        <input 
                            id="openingCashInput"
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                        <p class="text-xs text-yellow-600 mt-1 flex items-center gap-1">
                            <x-lucide name="info" class="w-3 h-3" />
                            Hitung hanya uang tunai (Kertas & Koin). Jangan masukkan Transfer/QRIS.
                        </p>
                    </div>

                    <!-- Expenses -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">Pengeluaran</label>
                            <button type="button" wire:click="addExpense" class="text-primary-600 text-sm font-medium flex items-center gap-1">
                                <x-lucide name="plus" class="w-4 h-4" /> Tambah
                            </button>
                        </div>
                        @foreach($expenses as $index => $expense)
                            <div class="flex gap-2 mb-2" wire:key="expense-{{ $index }}">
                                <input type="text" wire:model="expenses.{{ $index }}.description" class="flex-1 px-3 py-2 border border-gray-200 rounded-lg" placeholder="Keterangan">
                                <div x-data="moneyInput({{ $expense['amount'] ?? 0 }}, 'expenses.{{ $index }}.amount')" class="w-32">
                                    <input 
                                        type="text" 
                                        inputmode="numeric" 
                                        x-model="formatted" 
                                        @input="onInput($event)" 
                                        @focus="$event.target.select()"
                                        @blur="syncToWire()"
                                        class="w-full px-3 py-2 border border-gray-200 rounded-lg text-right" 
                                        placeholder="0"
                                    >
                                </div>
                                <button wire:click="removeExpense({{ $index }})" class="p-2 text-red-500 hover:bg-red-50 rounded-lg">
                                    <x-lucide name="trash-2" class="w-4 h-4" />
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <!-- Actual Cash (Physical in drawer) -->
                    <div x-data="moneyInput({{ $actualCash }}, 'actualCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Uang Fisik Tunai di Laci
                        </label>
                        <input 
                            id="actualCashInput"
                            type="text" 
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-primary-500" 
                            placeholder="0"
                        >
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <x-lucide name="info" class="w-3 h-3 shrink-0" />
                            Hitung HANYA uang kertas & koin di laci cash drawer.
                        </p>
                    </div>

                    <!-- Actual Non-Cash (QRIS/Transfer verified from bank) -->
                    <div x-data="moneyInput({{ $actualNonCash }}, 'actualNonCash')">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Uang Non-Tunai (QRIS / Transfer / EDC)
                        </label>
                        <input
                            id="actualNonCashInput"
                            type="text"
                            inputmode="numeric"
                            x-model="formatted"
                            @input="onInput($event)"
                            @focus="$event.target.select()"
                            @blur="syncToWire()"
                            class="w-full px-4 py-3 border border-gray-200 rounded-lg text-lg focus:ring-2 focus:ring-purple-500"
                            placeholder="0"
                        >
                        <p class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <x-lucide name="info" class="w-3 h-3 shrink-0" />
                            Cocokkan dengan mutasi rekening / dashboard QRIS sebelum mengisi.
                        </p>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                        <textarea wire:model="closeNotes" rows="2" class="w-full px-4 py-2 border border-gray-200 rounded-lg" placeholder="Catatan opsional"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t bg-gray-50 flex gap-3 rounded-b-2xl">
                    <button wire:click="$set('showCloseShiftModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Batal</button>
                    <button 
                        @click="
                            let opEl = document.getElementById('openingCashInput');
                            let acEl = document.getElementById('actualCashInput');
                            let ancEl = document.getElementById('actualNonCashInput');
                            if (!opEl || !acEl || !ancEl) {
                                showConfirmCloseShift = true;
                            } else {
                                let op = opEl.value;
                                let ac = acEl.value;
                                let anc = ancEl.value;
                                if(op.trim() === '' || ac.trim() === '' || anc.trim() === '') {
                                    $dispatch('notify', { type: 'error', message: 'Harap isi semua kolom wajib (isi 0 jika tidak ada)' });
                                } else {
                                    showConfirmCloseShift = true;
                                }
                            }
                        "
                        class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                    >
                        <x-lucide name="printer" class="w-5 h-5" />
                        Tutup & Cetak
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- History Modal -->
    @if($showHistoryModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-xl mx-4 shadow-2xl">
                <div class="p-5 border-b flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <x-lucide name="history" class="w-5 h-5 text-gray-500" />
                        Riwayat Transaksi Hari Ini
                    </h3>
                    <button wire:click="closeHistoryModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <x-lucide name="x" class="w-6 h-6" />
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
                                                <x-lucide name="printer" class="w-4 h-4" />
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
                <div class="p-4 border-t bg-gray-50 text-right rounded-b-2xl">
                    <button wire:click="closeHistoryModal" class="px-6 py-2 bg-gray-800 hover:bg-gray-900 text-white font-medium rounded-lg">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Return Modal -->
    @if($showReturnModal)
        <div
            x-data="{ _initReturnKey() { const k = crypto.randomUUID(); $wire.set('returnIdempotencyKey', k); } }"
            x-init="_initReturnKey()"
            class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8"
        >
            <div class="bg-white rounded-2xl w-full max-w-2xl mx-4 shadow-2xl">
                <div class="p-6 border-b flex justify-between items-center sticky top-0 bg-white z-10 rounded-t-2xl">
                    <h3 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                        <x-lucide name="rotate-ccw" class="w-6 h-6 text-red-600" />
                        Buat Retur
                    </h3>
                    <button wire:click="$set('showReturnModal', false)" class="text-gray-400 hover:text-gray-600">
                        <x-lucide name="x" class="w-6 h-6" />
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
                <div class="p-6 border-t bg-gray-50 flex gap-3 rounded-b-2xl">
                    <button wire:click="$set('showReturnModal', false)" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl">Batal</button>
                    @if($returnTransaction)
                        <button
                            wire:click.debounce.500ms="processReturn"
                            wire:loading.attr="disabled"
                            wire:target="processReturn"
                            class="flex-1 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span wire:loading.remove wire:target="processReturn">
                                <x-lucide name="rotate-ccw" class="w-5 h-5 inline" />
                                Proses Retur
                            </span>
                            <span wire:loading wire:target="processReturn" class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Return History Modal -->
    @if($showReturnHistoryModal)
        <div class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center overflow-y-auto py-8">
            <div class="bg-white rounded-2xl w-full max-w-xl mx-4 shadow-2xl">
                 <div class="p-5 border-b flex justify-between items-center sticky top-0 bg-white rounded-t-2xl z-10">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <x-lucide name="history" class="w-5 h-5 text-gray-500" />
                        Riwayat Retur Hari Ini
                    </h3>
                    <button wire:click="closeReturnHistoryModal" class="text-gray-400 hover:text-gray-600 p-1 rounded-lg hover:bg-gray-100">
                        <x-lucide name="x" class="w-6 h-6" />
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
                                                <x-lucide name="printer" class="w-4 h-4" />
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
                 <div class="p-4 border-t bg-gray-50 text-right rounded-b-2xl">
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
                        <x-lucide name="receipt" class="w-5 h-5" />
                        Struk Transaksi
                    </h3>
                    <button wire:click="closeReceiptModal" class="text-gray-400 hover:text-gray-600 p-1" type="button">
                        <x-lucide name="x" class="w-6 h-6" />
                    </button>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-container" class="p-4 overflow-y-auto flex-1 custom-scroll">
                    @include('livewire.partials.receipt', ['transaction' => $lastTransaction])
                </div>

                <div class="p-4 border-t flex gap-3 flex-none">
                    <button 
                        wire:click="printReceipt"
                        @click="console.log('Direct click print trigger'); window.print();"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                        type="button"
                    >
                        <x-lucide name="printer" class="w-5 h-5" />
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

    <!-- Shift Receipt Modal -->
    @if($showShiftReceiptModal && $printShift)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center p-4" style="z-index: 9999;">
            <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl max-h-[90vh] flex flex-col">
                <div class="p-4 border-b flex justify-between items-center flex-none">
                    <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                        <x-lucide name="receipt" class="w-5 h-5" />
                        Struk Laporan Shift
                    </h3>
                    <button wire:click="$set('showShiftReceiptModal', false)" class="text-gray-400 hover:text-gray-600 p-1" type="button">
                        <x-lucide name="x" class="w-6 h-6" />
                    </button>
                </div>

                <!-- Receipt Content -->
                <div id="receipt-container" class="p-4 overflow-y-auto flex-1 custom-scroll">
                    @include('livewire.partials.shift-receipt', ['shift' => $printShift])
                </div>

                <div class="p-4 border-t flex gap-3 flex-none">
                    <button
                        @click="window.print()"
                        class="flex-1 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-xl flex items-center justify-center gap-2"
                        type="button"
                    >
                        <x-lucide name="printer" class="w-5 h-5" />
                        Cetak Ulang
                    </button>
                    <button 
                        wire:click="$set('showShiftReceiptModal', false)"
                        class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl"
                        type="button"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif


    <!-- Custom Close Shift Confirmation Modal (Logout Style) -->
    <div 
        x-show="showConfirmCloseShift" 
        style="display: none;"
        class="fixed inset-0 z-[110] flex items-center justify-center bg-black/40 backdrop-blur-sm"
    >
        <div 
            x-show="showConfirmCloseShift"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-90"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-90"
            @click.away="showConfirmCloseShift = false"
            class="bg-white/90 backdrop-blur-md rounded-2xl shadow-2xl p-8 max-w-sm w-full mx-4 border border-white/50 text-center"
        >
            <div class="w-16 h-16 mx-auto rounded-full flex items-center justify-center mb-4 {{ ($pendingFoodCount > 0 || $pendingDrinkCount > 0) ? 'bg-red-100' : 'bg-red-100' }}">
                @if($pendingFoodCount > 0 || $pendingDrinkCount > 0)
                    <x-lucide name="alert-triangle" class="w-8 h-8 text-red-600" />
                @else
                    <x-lucide name="power" class="w-8 h-8 text-red-600" />
                @endif
            </div>
            
            <h3 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Tutup Shift</h3>
            
            <div class="text-gray-600 mb-6 text-sm">
                @if($pendingFoodCount > 0 || $pendingDrinkCount > 0)
                     <div class="bg-red-50 p-3 rounded-lg border border-red-200 mb-3 text-left">
                        <p class="font-bold text-red-800 mb-1 flex items-center gap-1">
                            ⚠️ Order Belum Selesai!
                        </p>
                        <ul class="list-disc list-inside text-xs text-red-700">
                            @if($pendingFoodCount > 0) <li>{{ $pendingFoodCount }} Makanan pending</li> @endif
                            @if($pendingDrinkCount > 0) <li>{{ $pendingDrinkCount }} Minuman pending</li> @endif
                        </ul>
                    </div>
                    <p class="font-medium text-red-600">Yakin ingin memaksa tutup?</p>
                @else
                    <p>Apakah Anda yakin ingin menutup shift ini dan mencetak laporan?</p>
                @endif
            </div>
            
            <div class="flex gap-3">
                <button 
                    @click="showConfirmCloseShift = false" 
                    class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition-colors"
                >
                    Batal
                </button>
                
                <button 
                    @click="$wire.closeShift().then(() => { showConfirmCloseShift = false; })"
                    wire:loading.attr="disabled"
                    class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-medium rounded-xl shadow-lg shadow-red-600/30 transition-colors flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <x-lucide name="printer" class="w-4 h-4" />
                    Ya, Tutup
                </button>
            </div>
        </div>
    </div>
    <!-- Pending Orders Modal -->
    @if($showPendingModal)
        <div class="fixed inset-0 bg-black/50 z-[100] flex items-center justify-center p-4 overflow-y-auto">
            <div class="bg-white rounded-2xl w-full max-w-2xl shadow-2xl overflow-hidden flex flex-col max-h-[85vh] animate-fade-in">
                <!-- Modal Header -->
                <div class="p-5 border-b bg-amber-50 flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-amber-100 text-amber-700 rounded-xl flex items-center justify-center font-bold">
                            <x-lucide name="clock" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Daftar Pesanan Pending (Ditunda)</h3>
                            <p class="text-xs text-gray-500">Pilih pesanan untuk dimuat kembali ke keranjang aktif</p>
                        </div>
                    </div>
                    <button wire:click="closePendingModal" class="p-2 text-gray-400 hover:text-gray-600 rounded-full hover:bg-white/50">
                        <x-lucide name="x" class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-5 overflow-y-auto flex-1 custom-scroll space-y-4">
                    @if(!empty($this->pendingCarts))
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($this->pendingCarts as $pending)
                                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 flex flex-col justify-between hover:bg-white hover:shadow-md transition-all">
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-start">
                                            <div class="flex items-center gap-2">
                                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                                <h4 class="font-bold text-gray-800 text-base leading-tight">{{ $pending['label'] ?? 'Pelanggan' }}</h4>
                                            </div>
                                            <span class="text-xs bg-amber-100 text-amber-800 px-2 py-0.5 rounded-md font-mono font-medium">
                                                {{ $pending['held_at'] ?? '-' }}
                                            </span>
                                        </div>

                                        <div class="text-xs text-gray-500 space-y-1 pt-1 border-t border-gray-100">
                                            <div class="flex justify-between">
                                                <span>Jumlah Item:</span>
                                                <span class="font-bold text-gray-700">{{ $pending['item_count'] ?? count($pending['cart']) }} item</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Total Tagihan:</span>
                                                <span class="font-extrabold text-primary-600">Rp {{ number_format($pending['total'] ?? 0, 0, ',', '.') }}</span>
                                            </div>
                                        </div>

                                        <!-- Items List Preview -->
                                        <div class="bg-white p-2.5 rounded-lg border border-gray-100 text-[11px] text-gray-600 space-y-1 max-h-24 overflow-y-auto custom-scroll">
                                            @foreach($pending['cart'] as $item)
                                                <div class="flex justify-between">
                                                    <span class="truncate max-w-[140px]">{{ $item['quantity'] }}x {{ $item['product_name'] }}</span>
                                                    <span class="font-medium">Rp {{ number_format($item['subtotal'] ?? 0, 0, ',', '.') }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="pt-3 mt-3 border-t border-gray-200 flex gap-2">
                                        <button 
                                            wire:click="confirmDeletePending('{{ $pending['id'] }}')"
                                            class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors text-xs font-bold flex items-center justify-center gap-1"
                                            title="Hapus Pending"
                                        >
                                            <x-lucide name="trash-2" class="w-4 h-4" />
                                        </button>
                                        
                                        <button 
                                            wire:click="restorePendingCart('{{ $pending['id'] }}')"
                                            class="flex-1 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold rounded-lg transition-colors text-xs flex items-center justify-center gap-1.5 shadow-sm"
                                        >
                                            <x-lucide name="rotate-ccw" class="w-4 h-4" />
                                            <span>Ambil Pesanan</span>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-400">
                            <x-lucide name="clock" class="w-12 h-12 mx-auto mb-3 opacity-40" />
                            <p class="font-bold text-gray-700">Tidak ada pesanan yang ditunda</p>
                            <p class="text-xs text-gray-500 mt-1">Gunakan tombol "Pending" pada keranjang untuk menunda pesanan</p>
                        </div>
                    @endif
                </div>

                <!-- Modal Footer -->
                <div class="p-4 bg-gray-50 border-t flex justify-end">
                    <button wire:click="closePendingModal" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold rounded-xl text-xs">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Custom Open Shift Confirmation Modal (bukan window.confirm() bawaan browser) -->
    @if($showOpenShiftModal)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[150] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl animate-fade-in space-y-4">
                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
                    <x-lucide name="power" class="w-7 h-7" />
                </div>

                <div>
                    <h3 class="text-lg font-bold text-gray-800">Buka Shift Sekarang?</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Shift baru akan dimulai atas nama <span class="font-semibold text-gray-700">{{ auth()->user()->name }}</span>.
                        Anda bisa langsung mulai bertransaksi setelah ini.
                    </p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button
                        wire:click="closeOpenShiftPrompt"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors text-xs"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="confirmOpenShift"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-60"
                        wire:target="confirmOpenShift"
                        class="flex-1 py-2.5 bg-green-600 hover:bg-green-700 text-white font-bold rounded-xl shadow-md shadow-green-600/30 transition-colors text-xs flex items-center justify-center gap-1.5"
                    >
                        <x-lucide name="power" class="w-4 h-4" />
                        <span wire:loading.remove wire:target="confirmOpenShift">Ya, Buka Shift</span>
                        <span wire:loading wire:target="confirmOpenShift">Membuka...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Custom Delete Pending Confirmation Modal (Theme-Matched) -->
    @if($confirmDeletePendingId)
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs z-[150] flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-sm w-full p-6 text-center shadow-2xl animate-fade-in space-y-4">
                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto shadow-inner">
                    <x-lucide name="alert-triangle" class="w-7 h-7" />
                </div>
                
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Hapus Pesanan Pending?</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        Pesanan ini akan dihapus dari daftar pending dan tidak dapat dikembalikan.
                    </p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button 
                        wire:click="cancelDeletePending"
                        class="flex-1 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-colors text-xs"
                    >
                        Batal
                    </button>
                    <button 
                        wire:click="deletePendingCart"
                        class="flex-1 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md shadow-red-600/30 transition-colors text-xs flex items-center justify-center gap-1.5"
                    >
                        <x-lucide name="trash-2" class="w-4 h-4" />
                        <span>Ya, Hapus</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Self Order Popup Modal di dalam POS -->
    @can('view_self_orders')
    <div x-show="showSelfOrderModal" x-cloak class="fixed inset-0 bg-black/60 z-[100] flex items-center justify-center p-2 sm:p-4">
        <div @click.away="showSelfOrderModal = false" class="bg-white rounded-2xl w-full max-w-4xl max-h-[85vh] shadow-2xl flex flex-col overflow-hidden border border-emerald-100">
            <div class="p-4 bg-emerald-600 text-white flex justify-between items-center flex-none">
                <div class="flex items-center gap-2 font-bold text-lg">
                    <x-lucide name="smartphone" class="w-6 h-6" />
                    <span>Pesanan Mandiri Pelanggan (QR Order)</span>
                </div>
                <button @click="showSelfOrderModal = false" class="text-white/80 hover:text-white p-1 rounded-lg hover:bg-white/10 transition">
                    <x-lucide name="x" class="w-6 h-6" />
                </button>
            </div>
            <div class="flex-1 overflow-y-auto custom-scroll">
                @livewire('admin.self-order-dashboard')
            </div>
        </div>
    </div>
    @endcan
</div>

@script
<script>
    // Catatan: broadcast ke Customer Display TIDAK lagi memakai catch-all "kirim update di setiap
    // commit Livewire apapun di halaman ini" (dulu lewat Livewire.hook('commit') + BroadcastChannel
    // di sini). Hook itu ikut kepicu oleh commit yang sama sekali tidak berhubungan dengan cart/QRIS
    // (mis. wire:poll notifikasi pesanan mandiri tiap 10 detik, atau commit dari komponen Livewire
    // lain di halaman ini), memaksa Customer Display melakukan $refresh() async berulang-ulang yang
    // jadi sumber beberapa race condition (gambar QRIS sempat tidak tampil, timer countdown sempat
    // "lompat" balik, tombol cek status sempat terduplikasi). Sekarang setiap perubahan state yang
    // relevan untuk Customer Display memanggil broadcastCartState() secara eksplisit (lihat
    // PosCheckout::broadcastCartState() & seluruh pemanggilnya, termasuk @cart-updated.window di
    // atas), dan Customer Display juga melakukan self-heal poll ringan setiap 5 detik di sisinya
    // sendiri sebagai jaring pengaman terakhir.

    // Open new window (For Shift Detail & Returns)
    const handleOpenNewWindow = (url) => {
        if (url) {
            console.log('Opening print window for URL:', url);
            if (url.includes('/print/')) {
                let iframe = document.getElementById('print-iframe');
                if (!iframe) {
                    iframe = document.createElement('iframe');
                    iframe.id = 'print-iframe';
                    iframe.style.position = 'fixed';
                    iframe.style.right = '0';
                    iframe.style.bottom = '0';
                    iframe.style.width = '0';
                    iframe.style.height = '0';
                    iframe.style.border = '0';
                    document.body.appendChild(iframe);
                }
                iframe.src = url;
            } else {
                window.open(url, '_blank');
            }
        }
    };

    // Support standard browser window event (Livewire 3 default dispatch behavior)
    window.addEventListener('open-new-window', (event) => {
        const url = event.detail?.url || (Array.isArray(event.detail) ? event.detail[0]?.url : null);
        handleOpenNewWindow(url);
    });

    // Support Livewire 3 $wire.on custom event listener fallback
    $wire.on('open-new-window', (data) => {
        let url = null;
        if (typeof data === 'string') {
            url = data;
        } else if (data && typeof data === 'object') {
            url = data.url || (Array.isArray(data) ? data[0]?.url : null);
        }
        handleOpenNewWindow(url);
    });
</script>
@endscript
