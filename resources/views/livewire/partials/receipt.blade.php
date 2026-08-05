@php
    $printerConfig = $printerConfig ?? ($this->printerConfig ?? null);
    $settings = $settings ?? ($this->generalSettings ?? []);
    $receiptSettings = $receiptSettings ?? ($this->receiptSettings ?? []);
    
    $paperWidth = match($printerConfig?->paper_size ?? '58mm') {
        '58mm' => '58mm',
        '80mm' => '80mm',
        'custom' => ($printerConfig->paper_width_px ?? 200) . 'px',
        default => '58mm',
    };
@endphp

<style>
    #receipt-content, #receipt-content * {
        color: #000 !important;
        border-color: #000 !important;
    }
    @media print {
        @page {
            size: {{ $paperWidth }} auto;
            margin: 2mm;
        }
        #receipt-content {
            width: {{ $paperWidth }};
            font-family: {{ $printerConfig?->font_family ?? 'monospace' }};
            font-size: {{ $printerConfig?->font_size_px ?? 12 }}px;
        }
    }
</style>

<div id="receipt-content" class="font-mono text-xs text-black" style="max-width: {{ $paperWidth }}; margin: 0 auto; color: #000 !important;">
    <!-- Header -->
    <div class="text-center border-b border-dashed border-black pb-3 mb-3">
        <h1 class="text-base font-bold">{{ $settings['store_name'] ?? 'Bakso Malang' }}</h1>
        <p class="text-xs">{{ $settings['store_address'] ?? '' }}</p>
        <p class="text-xs">{{ $settings['store_phone'] ?? '' }}</p>
    </div>

    <!-- Transaction Info -->
    <div class="border-b border-dashed border-black pb-3 mb-3 text-xs">
        <div class="flex justify-between">
            <span>No:</span>
            <span class="font-bold">{{ $transaction->invoice_number }}</span>
        </div>
        <div class="flex justify-between">
            <span>Tanggal:</span>
            <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="flex justify-between">
            <span>Kasir:</span>
            <span>{{ $transaction->user->name ?? '-' }}</span>
        </div>
        @if($transaction->customer_name)
        <div class="flex justify-between">
            <span>Customer:</span>
            <span>{{ $transaction->customer_name }}</span>
        </div>
        @endif
        <div class="flex justify-between">
            <span>Tipe:</span>
            <span class="font-bold">{{ $transaction->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}</span>
        </div>
        @if($transaction->service_area_id)
        <div class="flex justify-between">
            <span>Area / Meja:</span>
            <span class="font-bold text-md">{{ $transaction->serviceArea->name ?? '-' }}</span>
        </div>
        @endif
    </div>

    <!-- Items -->
    <div class="border-b border-dashed border-black pb-3 mb-3">
        @foreach($transaction->details as $detail)
            <div class="mb-2">
                <div class="flex justify-between">
                    <span class="flex-1 font-bold">{{ $detail->product_name }}</span>
                </div>
                <div class="flex justify-between pl-2">
                    <span>{{ $detail->quantity }} x Rp {{ number_format($detail->unit_price + $detail->modifier_total, 0, ',', '.') }}</span>
                    <span>Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($detail->modifiers->isNotEmpty())
                    <div class="pl-2 text-xs">
                        @foreach($detail->modifiers as $mod)
                            @php
                                $modQty = $mod->pivot->quantity ?? 1;
                                $modPrice = ($mod->pivot->price_adjustment ?? 0) * $modQty;
                            @endphp
                            + {{ $mod->pivot->modifier_name ?? $mod->name }}{{ $modQty > 1 ? " ×{$modQty}" : '' }}
                            @if($modPrice > 0)
                                (Rp {{ number_format($modPrice, 0, ',', '.') }})
                            @endif
                            @if(!$loop->last), @endif
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Totals -->
    <div class="border-b border-dashed border-black pb-3 mb-3">
        <div class="flex justify-between">
            <span>Subtotal:</span>
            <span>Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
        </div>
        @if($transaction->tax_amount > 0)
            <div class="flex justify-between">
                <span>Pajak:</span>
                <span>Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        @if($transaction->discount_amount > 0)
            <div class="flex justify-between">
                <span>Diskon:</span>
                <span>-Rp {{ number_format($transaction->discount_amount, 0, ',', '.') }}</span>
            </div>
        @endif
        <div class="flex justify-between font-bold text-sm mt-1">
            <span>TOTAL:</span>
            <span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Payment -->
    <div class="border-b border-dashed border-black pb-3 mb-3">
        <div class="flex justify-between">
            <span>Bayar ({{ ucfirst($transaction->payment_method) }}):</span>
            <span>Rp {{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
        </div>
        @if($transaction->change_amount > 0)
            <div class="flex justify-between font-bold">
                <span>Kembali:</span>
                <span>Rp {{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
            </div>
        @endif
    </div>

    @if($transaction->notes)
    <div class="border-b border-dashed border-black pb-3 mb-3 text-xs">
        <span class="font-bold">Catatan:</span>
        <p class="italic whitespace-pre-wrap">{{ $transaction->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="text-center text-xs">
        <p>{{ $receiptSettings['header_text'] ?? 'Terima Kasih!' }}</p>
        <p class="mt-1">{{ $receiptSettings['footer_text'] ?? '' }}</p>
    </div>

    <!-- Queue Number (Large) -->
    <div class="text-center mt-4 py-3 border-t border-dashed border-black">
        <p class="text-xs">NOMOR ANTRIAN</p>
        <p class="text-4xl font-bold">{{ $transaction->queue_display }}</p>
    </div>
</div>
