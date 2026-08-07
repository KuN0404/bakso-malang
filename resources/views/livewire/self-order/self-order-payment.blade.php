<div class="flex flex-col min-h-screen bg-gray-50"
    x-data="{
        sseConnected: false,
        connectSse() {
            const url = '{{ route('api.self-order.payment.sse', $token) }}';
            const es = new EventSource(url);
            this.sseConnected = true;
            es.addEventListener('payment_success', (e) => {
                const data = JSON.parse(e.data);
                window.location.href = data.tracking_url;
            });
            es.addEventListener('payment_failed', (e) => {
                $wire.set('isExpired', true);
                es.close();
            });
            es.onerror = () => { this.sseConnected = false; };
        }
    }"
    x-init="connectSse()">

    {{-- Header --}}
    @php
        $logoType = \App\Models\Setting::get('logo_type', 'single', 'general');
        $logoWeb = \App\Models\Setting::get('logo_web', null, 'general');
        $logoFull = \App\Models\Setting::get('logo_full', null, 'general');
    @endphp
    <header class="bg-brand text-white sticky top-0 z-10 shadow-sm">
        <div class="px-4 py-3.5 flex items-center justify-center gap-2">
            @if($logoType === 'full' && $logoFull)
                <img src="{{ asset('storage/' . $logoFull) }}" class="h-6 w-auto max-w-[130px] object-contain">
            @elseif($logoWeb)
                <img src="{{ asset('storage/' . $logoWeb) }}" class="w-6 h-6 object-cover rounded">
            @else
                <svg class="w-5 h-5 text-white/80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
            @endif
            <span class="font-bold text-[15px]">Pembayaran QRIS</span>
        </div>
    </header>

    {{-- Expired State --}}
    @if($isExpired)
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mb-5">
            <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <h2 class="text-gray-900 font-bold text-lg mb-1.5">QR Code Kadaluarsa</h2>
        <p class="text-gray-500 text-sm mb-7 max-w-[240px]">Waktu pembayaran sudah habis. Silakan buat pesanan baru untuk melanjutkan.</p>
        <a href="{{ route('self-order.index') }}"
            class="bg-brand hover:bg-blue-700 active:scale-[0.98] text-white px-8 py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-600/20 transition-all">
            Pesan Lagi
        </a>
    </div>

    {{-- QR Code State --}}
    @else
    <main class="flex-1 px-4 py-6">

        {{-- Amount + live status --}}
        <div class="text-center mb-6">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-1">Total Pembayaran</p>
            <p class="text-[28px] leading-tight font-extrabold text-gray-900">
                Rp {{ number_format($selfOrder?->total ?? 0, 0, ',', '.') }}
            </p>
            <div class="inline-flex items-center gap-1.5 mt-3 bg-blue-50 text-brand text-xs font-semibold px-3 py-1.5 rounded-full">
                <span class="relative flex h-1.5 w-1.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-brand opacity-75" x-show="sseConnected"></span>
                    <span class="relative inline-flex rounded-full h-1.5 w-1.5 bg-brand"></span>
                </span>
                <span x-text="sseConnected ? 'Menunggu pembayaran' : 'Memperbarui status…'"></span>
            </div>
        </div>

        {{-- QR Code Card --}}
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm mx-auto w-full max-w-[260px]">
            @if($this->qrCodeUrl)
                <img src="{{ $this->qrCodeUrl }}" alt="QR Code Pembayaran"
                    class="w-full aspect-square object-contain rounded-lg">
            @else
                <div class="w-full aspect-square bg-gray-50 rounded-lg flex items-center justify-center">
                    <div class="animate-spin w-7 h-7 border-[3px] border-gray-200 border-t-brand rounded-full"></div>
                </div>
            @endif
        </div>

        {{-- Countdown — wire:ignore: dihitung murni di client dari target waktu absolut (bukan
             dekrement lokal), supaya TIDAK PERNAH ter-reset ke awal walau Livewire me-render
             ulang komponen ini (klik "Cek Status Manual", dll). Progress bar juga memakai
             totalMs yang tetap/stabil, bukan sisa-waktu-saat-ini yang berubah tiap request. --}}
        <div
            x-data="{
                expiresAtMs: {{ $this->expiresAtEpochMs }},
                totalMs: {{ $this->totalExpirySeconds * 1000 }},
                remainingMs: 0,
                timer: null,
                tick() {
                    this.remainingMs = Math.max(0, this.expiresAtMs - Date.now());
                    if (this.remainingMs <= 0) {
                        clearInterval(this.timer);
                        $wire.set('isExpired', true);
                    }
                },
                formatted() {
                    const totalSec = Math.ceil(this.remainingMs / 1000);
                    const m = Math.floor(totalSec / 60);
                    const s = totalSec % 60;
                    return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
                },
                percent() {
                    if (!this.totalMs) return 0;
                    return Math.max(0, Math.min(100, (this.remainingMs / this.totalMs) * 100));
                }
            }"
            x-init="if (expiresAtMs) { tick(); timer = setInterval(() => tick(), 1000); }"
            wire:ignore
            class="mt-5 mx-auto max-w-[260px]">
            <div class="flex items-center justify-between text-xs font-medium text-gray-500 mb-1.5">
                <span>Berlaku hingga</span>
                <span class="font-mono font-bold text-gray-900 text-sm" x-text="formatted()"></span>
            </div>
            <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-brand transition-all duration-1000 rounded-full"
                    :style="`width: ${percent()}%`">
                </div>
            </div>
        </div>

        <p class="text-center text-xs text-gray-400 mt-4 max-w-[260px] mx-auto">
            Scan dari aplikasi GoPay, OVO, DANA, atau m-Banking mana pun yang mendukung QRIS
        </p>

        {{-- Fallback status message --}}
        @if($flashMessage)
        <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-700 text-xs px-4 py-3 rounded-xl text-center font-medium max-w-[260px] mx-auto">
            {{ $flashMessage }}
        </div>
        @endif

        {{-- Manual check — secondary action, SSE sudah otomatis --}}
        <button wire:click.debounce.500ms="checkPaymentStatus"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-60"
            wire:target="checkPaymentStatus"
            class="mt-5 mx-auto flex w-full max-w-[260px] items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white py-3 text-sm font-semibold text-gray-600 hover:bg-gray-50 active:scale-[0.98] transition-all">
            <svg class="w-4 h-4 flex-shrink-0" wire:loading.class="animate-spin" wire:target="checkPaymentStatus"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <span wire:loading.remove wire:target="checkPaymentStatus">Cek Status Manual</span>
            <span wire:loading wire:target="checkPaymentStatus">Memeriksa ke Midtrans…</span>
        </button>

        {{-- Order Info --}}
        @if($selfOrder)
        <div class="mt-6 bg-white border border-gray-100 rounded-xl p-4 text-sm space-y-2 max-w-[260px] mx-auto mb-8">
            <div class="flex justify-between">
                <span class="text-gray-400">No. Pesanan</span>
                <span class="text-gray-900 font-mono font-bold">#{{ $selfOrder->queue_display }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Nama</span>
                <span class="text-gray-700 font-medium">{{ $selfOrder->customer_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-400">Item</span>
                <span class="text-gray-700 font-medium">{{ $selfOrder->items->count() }} menu</span>
            </div>
        </div>
        @endif

    </main>
    @endif

</div>
