<div class="flex flex-col min-h-screen bg-gray-50"
    x-data
    wire:poll.{{ $refreshInterval }}ms.visible="refreshStatus">

    {{-- Header --}}
    <header class="bg-brand text-white px-4 py-4 shadow-md">
        <div class="flex items-center gap-3">
            <a href="{{ route('self-order.index') }}" class="text-white/70 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div>
                <h1 class="font-bold text-lg">Status Pesanan</h1>
                <p class="text-white/70 text-xs">Update otomatis setiap beberapa detik</p>
            </div>
        </div>
    </header>

    <div class="flex-1 px-4 py-6 space-y-4">

        {{-- Status State: Cancelled / Expired --}}
        @if($selfOrder?->isCancelled() || $selfOrder?->isExpired())
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6 text-center">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-gray-800 mb-1">Pesanan {{ $selfOrder->status->label() }}</h2>
            @if($selfOrder->cancelled_reason)
            <p class="text-gray-500 text-sm mb-4">{{ $selfOrder->cancelled_reason }}</p>
            @endif
            <a href="{{ route('self-order.index') }}"
                class="flex items-center justify-center gap-2 bg-brand hover:bg-blue-700 active:scale-[0.99] text-white rounded-xl py-2.5 px-6 font-semibold text-sm shadow-md shadow-blue-600/20 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Pesan Lagi</span>
            </a>
        </div>

        {{-- Normal tracking --}}
        @elseif($selfOrder)

        {{-- Queue Number --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
            <p class="text-gray-400 text-xs uppercase tracking-wide font-semibold mb-1">Nomor Antrian Anda</p>
            <div class="text-7xl font-black text-brand leading-none">
                {{ $selfOrder->queue_display }}
            </div>
            <div class="mt-3 inline-flex items-center gap-2 bg-{{ $selfOrder->status->color() }}-100 text-{{ $selfOrder->status->color() }}-700 px-4 py-1.5 rounded-lg text-sm font-semibold">
                {{ $selfOrder->status->label() }}
            </div>
        </div>

        {{-- Progress Steps --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <h3 class="font-semibold text-gray-700 text-sm mb-4">Progress Pesanan</h3>
            <div class="relative">
                {{-- Line --}}
                <div class="absolute left-4 top-4 bottom-4 w-0.5 bg-gray-100"></div>

                <div class="space-y-4">
                    @foreach($this->statusSteps as $step)
                    <div wire:key="step-{{ $step['key'] }}" class="flex items-start gap-4 relative">
                        <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center z-10
                            {{ $step['done']   ? 'bg-emerald-500' : '' }}
                            {{ $step['active'] ? 'bg-brand ring-4 ring-blue-100 animate-pulse' : '' }}
                            {{ !$step['done'] && !$step['active'] ? 'bg-gray-100' : '' }}">
                            @if($step['done'])
                                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @elseif($step['active'])
                                <div class="w-2.5 h-2.5 bg-white rounded-full"></div>
                            @else
                                <div class="w-2.5 h-2.5 bg-gray-300 rounded-full"></div>
                            @endif
                        </div>
                        <div class="pt-1">
                            <p class="text-sm font-semibold
                                {{ $step['active'] ? 'text-brand' : ($step['done'] ? 'text-emerald-600' : 'text-gray-400') }}">
                                {{ $step['label'] }}
                            </p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Order Items --}}
        @if($selfOrder->items->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 py-3 border-b font-semibold text-gray-700 text-sm">Detail Pesanan</div>
            <div class="divide-y divide-gray-50">
                @foreach($selfOrder->items as $item)
                <div wire:key="item-{{ $item->id }}" class="px-4 py-3 flex items-center gap-3">
                    <div class="w-6 h-6 bg-brand text-white rounded-lg flex items-center justify-center text-xs font-bold flex-shrink-0">
                        {{ $item->quantity }}
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-700">{{ $item->product_name }}</p>
                        @if($item->modifiers->count())
                        <p class="text-xs text-gray-400 mt-0.5">{{ $item->modifier_names }}</p>
                        @endif
                    </div>
                    <span class="text-sm font-semibold text-gray-600">{{ $item->formatted_subtotal }}</span>
                </div>
                @endforeach
            </div>
            <div class="border-t px-4 py-3 flex justify-between font-bold text-base">
                <span class="text-gray-700">Total</span>
                <span class="text-brand">{{ $selfOrder->formatted_total }}</span>
            </div>
        </div>
        @endif

        {{-- Customer Info & Proof of Payment --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 space-y-2 text-sm">
            <div class="flex justify-between text-gray-500">
                <span>Nama Pemesan</span>
                <span class="font-bold text-gray-800">{{ $selfOrder->customer_name }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
                <span>No. HP</span>
                <span class="font-medium text-gray-700">{{ $selfOrder->customer_phone }}</span>
            </div>
            <div class="flex justify-between text-gray-500">
                <span>Metode Pembayaran</span>
                <span class="font-bold text-emerald-600 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ $selfOrder->payment_method_label }}
                </span>
            </div>
            @if($selfOrder->invoice_number)
            <div class="flex justify-between text-gray-500">
                <span>No. Invoice</span>
                <span class="font-mono text-xs font-bold text-gray-700 bg-gray-100 px-2 py-0.5 rounded">{{ $selfOrder->invoice_number }}</span>
            </div>
            @endif
            @if($selfOrder->notes)
            <div class="flex justify-between text-gray-500">
                <span>Catatan</span>
                <span class="font-medium text-gray-700">{{ $selfOrder->notes }}</span>
            </div>
            @endif
        </div>

        {{-- Digital Receipt Action Button (Bukti Bayar Sah) --}}
        <div class="pt-2">
            <button onclick="window.print()"
                class="w-full bg-gray-900 text-white rounded-2xl py-3.5 px-4 font-semibold text-sm flex items-center justify-center gap-2 shadow-md hover:bg-black transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak / Simpan Struk Digital</span>
            </button>
            <p class="text-[11px] text-gray-400 text-center mt-2">
                Struk digital ini adalah bukti transaksi sah dari {{ config('app.name') }}.
            </p>
        </div>

        {{-- Pesan Lagi --}}
        @if($selfOrder->isCompleted())
        <div class="pt-2 pb-4">
            <a href="{{ route('self-order.index') }}"
                onclick="localStorage.removeItem('active_self_order_token')"
                class="flex items-center justify-center gap-2 w-full bg-brand hover:bg-blue-700 active:scale-[0.99] text-white rounded-2xl py-3.5 px-4 font-bold text-sm shadow-lg shadow-blue-600/20 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Pesan Lagi</span>
            </a>
        </div>
        @endif

        @else
        <div class="text-center py-12 text-gray-400">Order tidak ditemukan.</div>
        @endif
    </div>
</div>
