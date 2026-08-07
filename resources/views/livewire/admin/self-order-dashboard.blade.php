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
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                                @if($order->invoice_number)
                                    <span class="text-[11px] font-mono font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $order->invoice_number }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs bg-{{ $order->status->color() }}-100 text-{{ $order->status->color() }}-800 px-2.5 py-0.5 rounded-lg font-bold">
                                    {{ $order->status->label() }}
                                </span>
                                @if($order->payment_method === 'qris')
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="credit-card" class="w-3.5 h-3.5" />
                                        QRIS
                                    </span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="wallet" class="w-3.5 h-3.5" />
                                        Bayar di Kasir
                                    </span>
                                @endif
                                @if($order->order_type === 'dine_in')
                                    <span class="text-xs bg-purple-100 text-purple-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="utensils" class="w-3.5 h-3.5" />
                                        Makan di Tempat {{ $order->serviceArea ? '('.$order->serviceArea->name.')' : '(Belum Set Meja)' }}
                                    </span>
                                @else
                                    <span class="text-xs bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="shopping-bag" class="w-3.5 h-3.5" />
                                        Bawa Pulang
                                    </span>
                                @endif
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
                        <div class="w-12 h-12 bg-orange-500 text-white rounded-xl flex items-center justify-center font-black text-lg flex-shrink-0">
                            {{ $order->queue_display }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                                @if($order->invoice_number)
                                    <span class="text-[11px] font-mono font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $order->invoice_number }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs bg-orange-100 text-orange-800 px-2.5 py-0.5 rounded-lg font-bold">
                                    {{ $order->status->label() }}
                                </span>
                                <span class="text-xs bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                    <x-lucide name="wallet" class="w-3.5 h-3.5" />
                                    Bayar di Kasir
                                </span>
                                @if($order->order_type === 'dine_in')
                                    <span class="text-xs bg-purple-100 text-purple-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="utensils" class="w-3.5 h-3.5" />
                                        Makan di Tempat {{ $order->serviceArea ? '('.$order->serviceArea->name.')' : '(Belum Set Meja)' }}
                                    </span>
                                @else
                                    <span class="text-xs bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="shopping-bag" class="w-3.5 h-3.5" />
                                        Bawa Pulang
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                {{ $order->items->count() }} item ·
                                <span class="font-semibold text-gray-800">{{ $order->formatted_total }}</span>
                            </p>
                            <p class="text-xs text-orange-500 font-medium mt-0.5">
                                {{ $order->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

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

        @elseif($activeTab === 'claimed')
        <div class="space-y-3">
            <div class="flex items-center justify-between px-1 gap-2">
                <p class="text-xs text-gray-400">Pesanan yang sudah diambil kasir, baik QRIS maupun Bayar di Tempat.</p>
                <label class="flex items-center gap-1.5 text-xs font-medium text-gray-500 cursor-pointer select-none whitespace-nowrap">
                    <input type="checkbox" wire:click="toggleOnlyMine" @checked($onlyMyClaimedOrders)
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-3.5 h-3.5">
                    Punya saya saja
                </label>
            </div>

            @php
                $statusCounts = $this->claimedStatusCounts;
                $totalClaimed = array_sum($statusCounts);
                $subFilters = [
                    ''           => ['label' => 'Semua',          'color' => 'blue'],
                    'paid'       => ['label' => 'Sudah Dibayar',  'color' => 'blue'],
                    'processing' => ['label' => 'Diproses',       'color' => 'purple'],
                    'ready'      => ['label' => 'Siap Diambil',   'color' => 'cyan'],
                ];
            @endphp
            <div class="flex gap-1.5 flex-wrap">
                @foreach($subFilters as $filterVal => $filterMeta)
                    @php
                        $count = $filterVal === '' ? $totalClaimed : ($statusCounts[$filterVal] ?? 0);
                        $isActive = $claimedStatusFilter === $filterVal;
                        $colorActive = match($filterMeta['color']) {
                            'purple' => 'bg-purple-600 text-white border-purple-600',
                            'cyan'   => 'bg-cyan-600 text-white border-cyan-600',
                            default  => 'bg-blue-600 text-white border-blue-600',
                        };
                        $colorInactive = match($filterMeta['color']) {
                            'purple' => 'border-gray-200 text-gray-600 hover:bg-purple-50 hover:border-purple-300 hover:text-purple-700',
                            'cyan'   => 'border-gray-200 text-gray-600 hover:bg-cyan-50 hover:border-cyan-300 hover:text-cyan-700',
                            default  => 'border-gray-200 text-gray-600 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-700',
                        };
                    @endphp
                    <button wire:click="filterClaimedByStatus('{{ $filterVal }}')"
                        class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold border rounded-lg transition {{ $isActive ? $colorActive : $colorInactive }}">
                        {{ $filterMeta['label'] }}
                        @if($count > 0)
                            <span class="{{ $isActive ? 'bg-white/30 text-white' : 'bg-gray-100 text-gray-700' }} text-[10px] font-bold px-1.5 py-0 rounded min-w-[18px] text-center">
                                {{ $count }}
                            </span>
                        @endif
                    </button>
                @endforeach
            </div>
            @forelse($this->claimedOrders as $order)
            <div class="bg-white rounded-xl shadow-sm border border-blue-100 p-4" wire:key="claimed-{{ $order->id }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 bg-blue-500 text-white rounded-xl flex items-center justify-center font-black text-lg flex-shrink-0">
                            {{ $order->queue_display }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-gray-800">{{ $order->customer_name }}</p>
                                @if($order->invoice_number)
                                    <span class="text-[11px] font-mono font-semibold text-gray-500 bg-gray-100 px-2 py-0.5 rounded border border-gray-200">
                                        {{ $order->invoice_number }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-xs bg-{{ $order->status->color() }}-100 text-{{ $order->status->color() }}-800 px-2.5 py-0.5 rounded-lg font-bold">
                                    {{ $order->status->label() }}
                                </span>
                                @if($order->payment_method === 'qris')
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="credit-card" class="w-3.5 h-3.5" />
                                        QRIS
                                    </span>
                                @else
                                    <span class="text-xs bg-gray-100 text-gray-700 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="wallet" class="w-3.5 h-3.5" />
                                        Bayar di Kasir
                                    </span>
                                @endif
                                <span class="text-xs bg-blue-50 text-blue-700 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                    <x-lucide name="user" class="w-3.5 h-3.5" />
                                    {{ $order->processedBy?->name ?? '—' }}
                                </span>
                                @if($order->order_type === 'dine_in')
                                    <span class="text-xs bg-purple-100 text-purple-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="utensils" class="w-3.5 h-3.5" />
                                        Makan di Tempat {{ $order->serviceArea ? '('.$order->serviceArea->name.')' : '(Belum Set Meja)' }}
                                    </span>
                                @else
                                    <span class="text-xs bg-amber-100 text-amber-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                        <x-lucide name="shopping-bag" class="w-3.5 h-3.5" />
                                        Bawa Pulang
                                    </span>
                                    @if($order->pickup_confirmed_at)
                                        <span class="text-xs bg-emerald-100 text-emerald-800 px-2.5 py-0.5 rounded-lg font-bold inline-flex items-center gap-1">
                                            <x-lucide name="check-circle" class="w-3.5 h-3.5 text-emerald-600" />
                                            Pengambil: {{ $order->pickup_display_name }}
                                        </span>
                                    @endif
                                @endif
                            </div>
                            <p class="text-sm font-semibold text-gray-800 mt-1">{{ $order->formatted_total }}</p>
                            <p class="text-xs text-gray-400">Diambil {{ $order->claimed_at?->diffForHumans() ?? $order->created_at->diffForHumans() }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 flex-shrink-0">
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
                <div>
                    <h3 class="font-bold text-lg text-gray-800">Detail Order #{{ $selectedOrder->queue_display }}</h3>
                    @if($selectedOrder->invoice_number)
                        <p class="text-xs font-mono text-gray-500">Invoice: {{ $selectedOrder->invoice_number }}</p>
                    @endif
                </div>
                <button wire:click="closeDetail" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="p-4 space-y-4">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-400">Pemesan</span><p class="font-medium">{{ $selectedOrder->customer_name }}</p></div>
                    <div><span class="text-gray-400">HP Pemesan</span><p class="font-medium font-mono text-xs">{{ $selectedOrder->customer_phone }}</p></div>
                    <div>
                        <span class="text-gray-400">Tipe Order</span>
                        <div class="mt-0.5">
                            @if($selectedOrder->order_type === 'dine_in')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-lg">
                                    <x-lucide name="utensils" class="w-3.5 h-3.5" />
                                    Makan di Tempat
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-lg">
                                    <x-lucide name="shopping-bag" class="w-3.5 h-3.5" />
                                    Bawa Pulang
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-400">Meja / Area</span>
                        <p class="font-medium flex items-center gap-1">
                            {{ $selectedOrder->serviceArea?->name ?? ($selectedOrder->order_type === 'dine_in' ? 'Belum Set Meja' : '-') }}
                            @if($selectedOrder->order_type === 'dine_in')
                                <button wire:click="openAssignAreaModal({{ $selectedOrder->id }}, 'none')" class="text-xs text-purple-600 underline hover:text-purple-800 ml-1">
                                    Edit
                                </button>
                            @endif
                        </p>
                    </div>
                    <div>
                        <span class="text-gray-400">Pembayaran</span>
                        <div class="mt-0.5">
                            @if($selectedOrder->payment_method === 'qris')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-lg">
                                    <x-lucide name="credit-card" class="w-3.5 h-3.5" />
                                    QRIS
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">
                                    <x-lucide name="wallet" class="w-3.5 h-3.5" />
                                    {{ $selectedOrder->payment_method_label }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <span class="text-gray-400">Status</span>
                        <div class="mt-0.5">
                            <span class="inline-flex px-2.5 py-1 bg-{{ $selectedOrder->status->color() }}-100 text-{{ $selectedOrder->status->color() }}-800 text-xs font-bold rounded-lg">
                                {{ $selectedOrder->status->label() }}
                            </span>
                        </div>
                    </div>
                    
                    @if($selectedOrder->order_type === 'take_away')
                    <div class="col-span-2 p-2.5 bg-amber-50 rounded-xl border border-amber-200">
                        <span class="text-xs text-amber-800 font-semibold uppercase tracking-wide">Pengambil Pesanan (Take Away)</span>
                        <p class="font-bold text-gray-900 mt-0.5">{{ $selectedOrder->pickup_display_name }}</p>
                        <p class="text-xs font-mono text-gray-600">{{ $selectedOrder->pickup_display_phone }}</p>
                        @if($selectedOrder->pickup_confirmed_at)
                            <p class="text-[11px] text-emerald-700 font-medium mt-1">✓ Dikonfirmasi: {{ $selectedOrder->pickup_confirmed_at->format('d/m/Y H:i') }}</p>
                        @else
                            <p class="text-[11px] text-amber-700 italic mt-1">Belum dikonfirmasi serah terima</p>
                        @endif
                    </div>
                    @endif
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
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
            <h3 class="font-bold text-lg mb-4">Konfirmasi Pembayaran</h3>
            @if($this->payOrderData)
            <p class="text-sm text-gray-500 mb-1">Total yang harus dibayar:</p>
            <p class="text-3xl font-black text-brand mb-4">{{ $this->payOrderData->formatted_total }}</p>
            <div class="mb-4">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Nominal Diterima</label>
                <input wire:model.live.debounce.150ms="paidAmount" type="number" min="{{ $this->payOrderData->total }}"
                    class="mt-1 w-full rounded-xl border px-3 py-2.5 text-base font-bold text-center focus:outline-none focus:ring-2 focus:ring-red-500">
                @php
                    $numPaid = is_numeric($paidAmount) ? (float)$paidAmount : 0;
                    $orderTotal = (float)($this->payOrderData->total ?? 0);
                    $change = max(0, $numPaid - $orderTotal);
                @endphp
                @if($numPaid >= $orderTotal && $orderTotal > 0)
                <p class="text-green-600 text-sm mt-1 text-center font-bold">
                    Kembalian: Rp {{ number_format($change, 0, ',', '.') }}
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

    {{-- ─── Modal: Assign Service Area (Meja/Ruangan) ──────────────────── --}}
    @if($showAssignAreaModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 space-y-4">
            <div>
                <h3 class="font-bold text-lg text-gray-800">Pilih Meja / Area Makan</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Pesanan ini adalah tipe <span class="font-semibold text-purple-700">Makan di Tempat</span>. Harap tentukan lokasi meja terlebih dahulu.
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Meja / Area Makan (opsional jika isi Nomor Pager)</label>
                <select wire:model="selectedServiceAreaId" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Pilih Meja --</option>
                    @foreach($this->activeServiceAreas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }} ({{ $area->type_label }})</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1.5">Nomor Pager (opsional)</label>
                <select wire:model="selectedPagerId" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-purple-500">
                    <option value="">-- Tanpa Pager --</option>
                    @foreach($this->activePagers as $pager)
                        <option value="{{ $pager->id }}">
                            Pager {{ $pager->number }}{{ $pager->docking_number ? " (Docking {$pager->docking_number})" : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-3 pt-2">
                <button wire:click="closeAssignAreaModal" class="flex-1 py-2.5 border border-gray-200 text-gray-600 font-semibold text-sm rounded-xl hover:bg-gray-50">
                    Batal
                </button>
                <button wire:click="saveAreaAndContinue" class="flex-1 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-bold text-sm rounded-xl transition shadow-md shadow-purple-600/30">
                    Simpan & Lanjut
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- ─── Modal: Konfirmasi Pengambil (Take Away / Dine In — QRIS & Cash) ── --}}
    @if($showPickupModal && $pickupOrder)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 space-y-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <h3 class="font-bold text-lg text-gray-800">Konfirmasi Pengambil Pesanan</h3>
                    <div class="flex gap-1.5">
                        @if($pickupOrder->payment_method === 'qris')
                            <span class="px-2.5 py-1 bg-blue-100 text-blue-800 text-xs font-bold rounded-lg flex items-center gap-1">
                                <x-lucide name="credit-card" class="w-3.5 h-3.5" />
                                QRIS
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg flex items-center gap-1">
                                <x-lucide name="wallet" class="w-3.5 h-3.5" />
                                Bayar di Kasir
                            </span>
                        @endif
                        @if($pickupOrder->order_type === 'dine_in')
                            <span class="px-2.5 py-1 bg-purple-100 text-purple-800 text-xs font-bold rounded-lg flex items-center gap-1">
                                <x-lucide name="utensils" class="w-3.5 h-3.5" />
                                Makan di Tempat
                            </span>
                        @else
                            <span class="px-2.5 py-1 bg-amber-100 text-amber-800 text-xs font-bold rounded-lg flex items-center gap-1">
                                <x-lucide name="shopping-bag" class="w-3.5 h-3.5" />
                                Bawa Pulang
                            </span>
                        @endif
                    </div>
                </div>
                <p class="text-xs text-gray-500">
                    Antrian: <strong class="font-mono text-gray-800">#{{ $pickupOrder->queue_display }}</strong>
                    @if($pickupOrder->invoice_number)
                        &bull; Inv: <strong class="font-mono text-gray-800">{{ $pickupOrder->invoice_number }}</strong>
                    @endif
                    &bull;
                    <span class="font-semibold text-gray-700">
                        {{ $pickupNextAction === 'claim' ? 'Sebelum Diambil Kasir' : 'Sebelum Diselesaikan' }}
                    </span>
                </p>
            </div>

            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100 text-sm space-y-1">
                <p class="text-xs text-gray-400 uppercase font-semibold flex items-center gap-1">
                    <x-lucide name="user" class="w-3.5 h-3.5 text-gray-500" />
                    Pemesan Asli
                </p>
                <p class="font-bold text-gray-800">{{ $pickupOrder->customer_name }}</p>
                <p class="text-xs text-gray-500 font-mono">{{ $pickupOrder->customer_phone }}</p>
            </div>

            <div class="space-y-3">
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wide">Siapa yang mengambil pesanan? *</label>

                <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition {{ $pickupOption === 'same' ? 'border-emerald-500 bg-emerald-50/50 text-emerald-900 font-medium' : 'border-gray-200 hover:bg-gray-50' }}">
                    <input type="radio" wire:model.live="pickupOption" value="same" class="mt-1 text-emerald-600 focus:ring-emerald-500">
                    <div>
                        <p class="text-sm font-bold flex items-center gap-1.5">
                            <x-lucide name="user" class="w-4 h-4 text-emerald-600" />
                            Orang yang Sama (Pemesan)
                        </p>
                        <p class="text-xs opacity-75 mt-0.5">{{ $pickupOrder->customer_name }} &bull; {{ $pickupOrder->customer_phone }}</p>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3 border rounded-xl cursor-pointer transition {{ $pickupOption === 'other' ? 'border-blue-500 bg-blue-50/50 text-blue-900 font-medium' : 'border-gray-200 hover:bg-gray-50' }}">
                    <input type="radio" wire:model.live="pickupOption" value="other" class="mt-1 text-blue-600 focus:ring-blue-500">
                    <div>
                        <p class="text-sm font-bold flex items-center gap-1.5">
                            <x-lucide name="users" class="w-4 h-4 text-blue-600" />
                            Orang Lain / Wakil / Kurir
                        </p>
                        <p class="text-xs opacity-75 mt-0.5">Masukkan nama & No. HP penjemput</p>
                    </div>
                </label>

                @if($pickupOption === 'other')
                <div class="space-y-2 pt-2 border-t border-gray-100">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Nama Pengambil *</label>
                        <input type="text" wire:model="pickupName" placeholder="Contoh: Budi (Adik / Ojol)" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                        @error('pickupName') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">No. HP Pengambil *</label>
                        <input type="text" wire:model="pickupPhone" placeholder="Contoh: 08123456789" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                        @error('pickupPhone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>
                </div>
                @endif
            </div>

            <div class="flex gap-3 pt-3 border-t border-gray-100">
                <button type="button" wire:click="closePickupModal" class="flex-1 py-2.5 text-sm font-semibold text-gray-600 border border-gray-200 rounded-xl hover:bg-gray-50">
                    Batal
                </button>
                <button type="button" wire:click="confirmPickup"
                    wire:loading.attr="disabled" wire:loading.class="opacity-60"
                    class="flex-1 py-2.5 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md transition">
                    <span wire:loading.remove wire:target="confirmPickup">
                        {{ $pickupNextAction === 'claim' ? 'Konfirmasi & Ambil Pesanan' : 'Konfirmasi & Selesaikan' }}
                    </span>
                    <span wire:loading wire:target="confirmPickup">Memproses...</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
