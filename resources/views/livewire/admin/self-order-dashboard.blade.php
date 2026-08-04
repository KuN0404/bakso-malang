<div>
    {{-- ─── Flash Notification ─────────────────────────────────────────── --}}
    @if($flashMessage)
    <div x-data="{ show: true }"
         x-init="setTimeout(() => { show = false; $wire.set('flashMessage', ''); }, 4000)"
         x-show="show"
         x-transition:enter="transition ease-out duration-300 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-end="opacity-0"
         class="fixed top-4 right-4 z-50 max-w-sm">
        <div class="flex items-start gap-3 px-4 py-3 rounded-xl shadow-xl text-sm font-medium
            {{ $flashType === 'success' ? 'bg-green-600 text-white' : ($flashType === 'error' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white') }}">
            @if($flashType === 'success')
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            @elseif($flashType === 'info')
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            @else
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
            @endif
            <span>{{ $flashMessage }}</span>
        </div>
    </div>
    @endif

    {{-- ─── Header ──────────────────────────────────────────────────────── --}}
    <div class="bg-white border-b px-6 py-4 flex items-center justify-between">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Dashboard Pesan Mandiri</h1>
            <p class="text-sm text-gray-400">Update otomatis setiap 5 detik</p>
        </div>
        <div class="text-xs text-gray-400" wire:poll.30000ms>
            {{ now()->format('H:i:s') }}
        </div>
    </div>

    {{-- ─── Tabs ────────────────────────────────────────────────────────── --}}
    <div class="border-b bg-white">
        <div class="flex">
            <button wire:click="switchTab('paid')"
                class="flex-1 py-3 text-sm font-semibold flex items-center justify-center gap-2 border-b-2 transition
                    {{ $activeTab === 'paid' ? 'border-emerald-600 text-emerald-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Sudah Dibayar
                @if($this->paidCount > 0)
                <span class="bg-green-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
                    {{ $this->paidCount }}
                </span>
                @endif
            </button>
            <button wire:click="switchTab('waiting')"
                class="flex-1 py-3 text-sm font-semibold flex items-center justify-center gap-2 border-b-2 transition
                    {{ $activeTab === 'waiting' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                Bayar di Tempat
                @if($this->waitingCount > 0)
                <span class="bg-orange-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
                    {{ $this->waitingCount }}
                </span>
                @endif
            </button>
            <button wire:click="switchTab('claimed')"
                class="flex-1 py-3 text-sm font-semibold flex items-center justify-center gap-2 border-b-2 transition
                    {{ $activeTab === 'claimed' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Diambil
                @if($this->claimedCount > 0)
                <span class="bg-blue-500 text-white text-xs font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
                    {{ $this->claimedCount }}
                </span>
                @endif
            </button>
        </div>
    </div>

    {{-- Poll setiap 10 detik saat tab aktif (bukan 5 detik) untuk kurangi server load --}}
    <div class="p-4" wire:poll.10000ms.visible>

        {{-- TAB: Sudah Dibayar (pool — belum diambil siapapun) --}}
        @if($activeTab === 'paid')
        <div class="space-y-3">
            <p class="text-xs text-gray-400 px-1">Pesanan yang sudah dibayar tapi belum diambil kasir mana pun.</p>
            @forelse($this->paidOrders as $order)
            <div class="bg-white rounded-xl shadow-sm border border-green-100 p-4" wire:key="paid-{{ $order->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-green-500 text-white rounded-xl flex items-center justify-center font-black text-lg flex-shrink-0">
                            {{ $order->queue_display }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs bg-{{ $order->status->color() }}-100 text-{{ $order->status->color() }}-700 px-2 py-0.5 rounded-full font-medium">
                                    {{ $order->status->label() }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $order->payment_method_label }}</span>
                            </div>
                            <p class="text-sm font-semibold text-gray-800 mt-1">{{ $order->formatted_total }}</p>
                            <p class="text-xs text-gray-400">{{ $order->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 flex-shrink-0">
                        @can('manage_self_orders')
                        <button wire:click="claimOrder({{ $order->id }})"
                            class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-lg font-semibold hover:bg-blue-100 transition whitespace-nowrap">
                            Ambil Pesanan
                        </button>
                        @endcan

                        <button wire:click="viewDetail({{ $order->id }})"
                            class="text-xs text-gray-400 hover:text-gray-600 transition text-center">
                            Detail
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="w-14 h-14 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="font-medium text-gray-500">Tidak ada pesanan yang perlu diambil</p>
            </div>
            @endforelse

            {{ $this->paidOrders->links() }}
        </div>

        {{-- TAB: Bayar di Tempat (pool — belum diambil siapapun) --}}
        @elseif($activeTab === 'waiting')
        <div class="space-y-3">
            <p class="text-xs text-gray-400 px-1">Pesanan yang belum dibayar dan belum diambil kasir mana pun.</p>
            @forelse($this->waitingOrders as $order)
            <div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4" wire:key="waiting-{{ $order->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        {{-- Nomor antrian --}}
                        <div class="w-12 h-12 bg-orange-500 text-white rounded-xl flex items-center justify-center font-black text-lg flex-shrink-0">
                            {{ $order->queue_display }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                            <p class="text-xs text-gray-400">{{ $order->customer_phone }}</p>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $order->items->count() }} item ·
                                <span class="font-semibold text-gray-800">{{ $order->formatted_total }}</span>
                            </p>
                            <p class="text-xs text-orange-500 font-medium mt-0.5">
                                {{ $order->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        @can('accept_self_order_payment')
                        <button wire:click="claimOrder({{ $order->id }})"
                            class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-3 py-1.5 rounded-lg font-semibold hover:bg-blue-100 transition whitespace-nowrap">
                            Ambil Pesanan
                        </button>
                        <button wire:click="openPaymentModal({{ $order->id }})"
                            class="text-xs bg-green-500 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-green-600 transition whitespace-nowrap">
                            Konfirmasi Bayar
                        </button>
                        @endcan
                        <button wire:click="viewDetail({{ $order->id }})"
                            class="text-xs text-gray-400 hover:text-gray-600 transition text-center">
                            Detail
                        </button>
                    </div>
                </div>

                {{-- Items Summary --}}
                <div class="mt-3 pt-3 border-t border-gray-50">
                    @foreach($order->items->take(3) as $item)
                    <p class="text-xs text-gray-500">• {{ $item->quantity }}× {{ $item->product_name }}</p>
                    @endforeach
                    @if($order->items->count() > 3)
                    <p class="text-xs text-gray-400">+{{ $order->items->count() - 3 }} item lainnya</p>
                    @endif
                </div>
            </div>
            @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="w-14 h-14 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="font-medium text-gray-500">Tidak ada pesanan Bayar di Tempat yang menunggu</p>
            </div>
            @endforelse

            {{ $this->waitingOrders->links() }}
        </div>

        {{-- TAB: Diambil (QRIS & Cash sekaligus — sedang dikerjakan kasir) --}}
        @elseif($activeTab === 'claimed')
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1">
                <p class="text-xs text-gray-400">Pesanan yang sudah diambil kasir, baik QRIS maupun Bayar di Tempat.</p>
                <label class="flex items-center gap-1.5 text-xs font-medium text-gray-500 cursor-pointer select-none whitespace-nowrap">
                    <input type="checkbox" wire:click="toggleOnlyMine" @checked($onlyMyClaimedOrders)
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                    Punya saya saja
                </label>
            </div>
            @forelse($this->claimedOrders as $order)
            <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4" wire:key="claimed-{{ $order->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-xl flex items-center justify-center font-black text-lg flex-shrink-0">
                            {{ $order->queue_display }}
                        </div>
                        <div>
                            <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                            <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                <span class="text-xs bg-{{ $order->status->color() }}-100 text-{{ $order->status->color() }}-700 px-2 py-0.5 rounded-full font-medium">
                                    {{ $order->status->label() }}
                                </span>
                                <span class="text-xs text-gray-400">{{ $order->payment_method_label }}</span>
                                <span class="text-xs text-blue-500 font-medium flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    {{ $order->processedBy?->name ?? '—' }}
                                </span>
                            </div>
                            <p class="text-sm font-semibold text-gray-800 mt-1">{{ $order->formatted_total }}</p>
                            <p class="text-xs text-gray-400">Diambil {{ $order->claimed_at?->diffForHumans() ?? $order->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 flex-shrink-0">
                        {{-- Tombol proses HANYA untuk kasir yang mengambil order ini — kasir lain cuma bisa lihat. --}}
                        @if($order->processed_by === auth()->id())
                        @can('accept_self_order_payment')
                        @if($order->isWaitingPayment())
                        <button wire:click="openPaymentModal({{ $order->id }})"
                            class="text-xs bg-green-500 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-green-600 transition whitespace-nowrap">
                            Konfirmasi Bayar
                        </button>
                        @endif
                        @endcan

                        @can('manage_self_orders')
                        @if($order->isPaid())
                        <button wire:click="updateStatus({{ $order->id }}, 'processing')"
                            class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-3 py-1.5 rounded-lg font-semibold hover:bg-purple-100 transition">
                            → Diproses
                        </button>
                        @elseif($order->isProcessing())
                        <button wire:click="updateStatus({{ $order->id }}, 'ready')"
                            class="text-xs bg-cyan-50 text-cyan-700 border border-cyan-200 px-3 py-1.5 rounded-lg font-semibold hover:bg-cyan-100 transition">
                            → Siap
                        </button>
                        @elseif($order->isReady())
                        <button wire:click="updateStatus({{ $order->id }}, 'completed')"
                            class="text-xs bg-green-500 text-white px-3 py-1.5 rounded-lg font-semibold hover:bg-green-600 transition">
                            ✓ Selesai
                        </button>
                        @endif
                        @endcan
                        @else
                        <span class="text-[11px] text-gray-400 italic text-right whitespace-nowrap">Bukan pesanan Anda</span>
                        @endif

                        @can('print_self_order_receipt')
                        @if(!$order->isWaitingPayment())
                        <button wire:click="printReceipt({{ $order->id }})"
                            class="text-xs text-gray-400 hover:text-gray-600 transition">
                            Cetak Struk
                        </button>
                        @endif
                        @endcan

                        <button wire:click="viewDetail({{ $order->id }})"
                            class="text-xs text-gray-400 hover:text-gray-600 transition text-center">
                            Detail
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="text-center py-16 text-gray-400">
                <svg class="w-14 h-14 mx-auto mb-3 opacity-20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <p class="font-medium text-gray-500">
                    {{ $onlyMyClaimedOrders ? 'Anda belum mengambil pesanan apa pun' : 'Belum ada pesanan yang diambil' }}
                </p>
            </div>
            @endforelse

            {{ $this->claimedOrders->links() }}
        </div>
        @endif
    </div>

    {{-- ─── Modal: Detail Order ─────────────────────────────────────────── --}}
    @if($showDetailModal && $selectedOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md max-h-[80vh] overflow-y-auto">
            <div class="p-4 border-b flex items-center justify-between">
                <h3 class="font-bold text-lg">Detail Order #{{ $selectedOrder->queue_display }}</h3>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-400">Nama</span><p class="font-medium">{{ $selectedOrder->customer_name }}</p></div>
                    <div><span class="text-gray-400">HP</span><p class="font-medium">{{ $selectedOrder->customer_phone }}</p></div>
                    <div><span class="text-gray-400">Pembayaran</span><p class="font-medium">{{ $selectedOrder->payment_method_label }}</p></div>
                    <div><span class="text-gray-400">Status</span><p class="font-medium text-brand">{{ $selectedOrder->status->label() }}</p></div>
                </div>
                <div class="border-t pt-3 space-y-2">
                    @foreach($selectedOrder->items as $item)
                    <div class="flex justify-between text-sm">
                        <div>
                            <span class="font-medium">{{ $item->quantity }}× {{ $item->product_name }}</span>
                            @if($item->modifiers->count())
                            <p class="text-xs text-gray-400">{{ $item->modifier_names }}</p>
                            @endif
                        </div>
                        <span class="font-semibold">{{ $item->formatted_subtotal }}</span>
                    </div>
                    @endforeach
                </div>
                <div class="border-t pt-3 flex justify-between font-bold text-base">
                    <span>Total</span>
                    <span class="text-brand">{{ $selectedOrder->formatted_total }}</span>
                </div>

                @can('cancel_self_order')
                @if($selectedOrder->isCancellable())
                <button wire:click="cancelOrderFromDetail"
                    class="w-full text-red-500 border border-red-200 py-2 rounded-xl text-sm font-semibold hover:bg-red-50 transition">
                    Batalkan Order
                </button>
                @endif
                @endcan
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Modal: Konfirmasi Pembayaran ───────────────────────────────── --}}
    @if($showPaymentModal)
    @php $payOrder = App\Models\SelfOrder::find($selectedOrderId); @endphp
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-lg mb-4">Konfirmasi Pembayaran</h3>
            @if($payOrder)
            <p class="text-sm text-gray-500 mb-1">Total yang harus dibayar:</p>
            <p class="text-3xl font-black text-brand mb-4">{{ $payOrder->formatted_total }}</p>
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nominal Diterima</label>
                <input wire:model="paidAmount" type="number" min="{{ $payOrder->total }}"
                    class="mt-1 w-full rounded-xl border px-3 py-2.5 text-base font-bold text-center focus:outline-none focus:ring-2 focus:ring-red-500">
                @if($paidAmount >= $payOrder->total)
                <p class="text-green-600 text-sm mt-1 text-center font-medium">
                    Kembalian: Rp {{ number_format(max(0, $paidAmount - $payOrder->total), 0, ',', '.') }}
                </p>
                @endif
            </div>
            @endif
            <div class="flex gap-3">
                <button wire:click="closePaymentModal" class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-semibold">
                    Batal
                </button>
                <button wire:click="confirmPayment"
                    wire:loading.attr="disabled"
                    wire:loading.class="opacity-50"
                    class="flex-1 bg-green-500 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-green-600 transition">
                    <span wire:loading.remove wire:target="confirmPayment">Konfirmasi</span>
                    <span wire:loading wire:target="confirmPayment">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Modal: Batalkan Order ───────────────────────────────────────── --}}
    @if($showCancelModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-lg mb-4 text-red-600">Batalkan Order</h3>
            <p class="text-sm text-gray-500 mb-3">Tindakan ini akan melepas semua reservation stok. Masukkan alasan pembatalan:</p>
            <textarea wire:model="cancelReason" rows="3" placeholder="Alasan pembatalan..."
                class="w-full border rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-500 resize-none mb-4"></textarea>
            <div class="flex gap-3">
                <button wire:click="closeCancelModal" class="flex-1 border border-gray-200 text-gray-600 py-2.5 rounded-xl text-sm font-semibold">
                    Kembali
                </button>
                <button wire:click="cancelOrder"
                    class="flex-1 bg-red-500 text-white py-2.5 rounded-xl text-sm font-bold hover:bg-red-600 transition">
                    Batalkan Order
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
