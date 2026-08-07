@push('head')
    <meta name="robots" content="noindex, nofollow">
@endpush

@php
    $logoType = \App\Models\Setting::get('logo_type', 'single', 'general');
    $logoWeb = \App\Models\Setting::get('logo_web', null, 'general');
    $logoFull = \App\Models\Setting::get('logo_full', null, 'general');
    $settings = \App\Models\Setting::getGroup('general');
    $receiptSettings = \App\Models\Setting::getGroup('receipt');
@endphp

<div class="flex flex-col min-h-screen bg-gray-50">
    {{-- Header --}}
    <header class="bg-brand text-white px-4 py-4 shadow-md">
        <div class="flex items-center gap-3">
            @if($logoType === 'full' && $logoFull)
                <img src="{{ asset('storage/' . $logoFull) }}" class="h-6 w-auto max-w-[130px] object-contain">
            @elseif($logoWeb)
                <img src="{{ asset('storage/' . $logoWeb) }}" class="w-6 h-6 object-cover rounded">
            @endif
            <div>
                <h1 class="font-bold text-lg">Struk Pembelian</h1>
                <p class="text-white/70 text-xs">{{ $settings['store_name'] ?? 'Toko' }}</p>
            </div>
        </div>
    </header>

    <div class="flex-1 px-4 py-6 space-y-4">
        @if(!$transaction)
            {{-- Not Found State --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 text-center">
                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-1">Struk Tidak Ditemukan</h2>
                <p class="text-gray-500 text-sm">Link ini tidak valid atau sudah tidak tersedia.</p>
            </div>
        @else
            {{-- Info Toko --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 text-center">
                <h2 class="font-bold text-gray-800">{{ $settings['store_name'] ?? 'Toko' }}</h2>
                @if(!empty($settings['store_address']))
                    <p class="text-xs text-gray-500 mt-1">{{ $settings['store_address'] }}</p>
                @endif
            </div>

            {{-- Info Transaksi --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">No. Invoice</span>
                    <span class="font-semibold text-gray-800">{{ $transaction->invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tanggal</span>
                    <span class="font-semibold text-gray-800">{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Tipe</span>
                    <span class="font-semibold text-gray-800">{{ $transaction->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}</span>
                </div>
                @if($transaction->serviceArea)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Area / Meja</span>
                        <span class="font-semibold text-gray-800">{{ $transaction->serviceArea->name }}</span>
                    </div>
                @endif
                @if($transaction->pager)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nomor Pager</span>
                        <span class="font-semibold text-gray-800">{{ $transaction->pager->number }}</span>
                    </div>
                @endif
            </div>

            {{-- Items --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                @foreach($transaction->details as $detail)
                    <div class="pb-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                        <div class="flex justify-between text-sm font-semibold text-gray-800">
                            <span>{{ $detail->product_name }}</span>
                            <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $detail->quantity }} x Rp {{ number_format($detail->unit_price, 0, ',', '.') }}</p>
                        @foreach($detail->modifiers as $mod)
                            @php
                                $modQty = $mod->pivot->quantity ?? 1;
                                $modPrice = ($mod->pivot->price_adjustment ?? 0) * $modQty;
                            @endphp
                            <p class="text-xs text-gray-400 mt-0.5">
                                + {{ $mod->pivot->modifier_name ?? $mod->name }}{{ $modQty > 1 ? " ×{$modQty}" : '' }}
                                @if($modPrice > 0) (Rp {{ number_format($modPrice, 0, ',', '.') }}) @endif
                            </p>
                        @endforeach
                    </div>
                @endforeach
            </div>

            {{-- Totals --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-500">Subtotal</span>
                    <span class="text-gray-800">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->tax_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pajak</span>
                        <span class="text-gray-800">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                @if($transaction->discount_amount > 0)
                    <div class="flex justify-between">
                        <span class="text-gray-500">Diskon</span>
                        <span class="text-gray-800">-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between text-base font-bold pt-2 border-t border-gray-100">
                    <span>TOTAL</span>
                    <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- Footer --}}
            <div class="text-center text-xs text-gray-400 pb-6">
                <p>{{ $receiptSettings['footer_text'] ?? 'Terima kasih atas kunjungan Anda!' }}</p>
            </div>
        @endif
    </div>
</div>
