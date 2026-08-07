<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Self Order #{{ $selfOrder->queue_display }}</title>
    @php
        $printerConfig = \App\Models\PrinterConfig::getDefault();
        $settings = \App\Models\Setting::getGroup('general');
        $receiptSettings = \App\Models\Setting::getGroup('receipt');

        $paperWidth = $printerConfig?->css_width ?? '58mm';
        $paperMargin = $printerConfig?->css_margin ?? '0mm 0mm 0mm 0mm';
        $fontFamily = $printerConfig?->font_family ?? 'monospace';
        $fontSize   = $printerConfig?->font_size_px ?? 12;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; color: #000 !important; }
        body { font-family: {{ $fontFamily }}; font-size: {{ $fontSize }}px; background: #f3f4f6; color: #000; }
        #receipt-content { width: {{ $paperWidth }}; margin: 16px auto; background: #fff; padding: 10px; border: 1px solid #000; overflow: hidden; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #000; margin: 5px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; width: 100%; max-width: 100%; overflow: hidden; }
        .row > * { min-width: 0; flex-shrink: 1; overflow-wrap: break-word; word-break: break-word; }
        .row.indent { padding-left: 10px; }
        .label { color: #000; font-size: 0.85em; }
        .store-name { font-size: 1.2em; font-weight: bold; text-transform: uppercase; }
        .queue { font-size: 2.6em; font-weight: 900; line-height: 1; }
        .no-print { text-align: center; margin-top: 16px; }
        @media print {
            body { background: #fff; }
            #receipt-content { width: {{ $paperWidth }} !important; margin: 0; padding: 2mm; border: none; }
            .no-print { display: none; }
            @page { size: {{ $paperWidth }} auto; margin: {{ $paperMargin }}; }
        }
    </style>
</head>
<body>
    <div id="receipt-content">
        {{-- Header (identik dengan struk POS biasa) --}}
        <div class="center">
            <p class="store-name">{{ $settings['store_name'] ?? config('app.name', 'Resto') }}</p>
            @if(!empty($settings['store_address']))<p class="label">{{ $settings['store_address'] }}</p>@endif
            @if(!empty($settings['store_phone']))<p class="label">{{ $settings['store_phone'] }}</p>@endif
            <p class="label">Struk Pesanan Mandiri</p>
        </div>
        <div class="divider"></div>

        {{-- Nomor antrian besar --}}
        <div class="center">
            <p class="label">NOMOR ANTRIAN</p>
            <p class="queue">{{ $selfOrder->queue_display }}</p>
        </div>
        <div class="divider"></div>

        {{-- Info transaksi --}}
        <div class="row"><span class="label">Nama</span><span class="bold">{{ $selfOrder->customer_name }}</span></div>
        <div class="row"><span class="label">HP</span><span>{{ $selfOrder->customer_phone }}</span></div>
        @if($selfOrder->invoice_number)
        <div class="row"><span class="label">Invoice</span><span>{{ $selfOrder->invoice_number }}</span></div>
        @endif
        <div class="row"><span class="label">Tipe</span><span class="bold">{{ $selfOrder->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}</span></div>
        @if($selfOrder->service_area_id)
        <div class="row"><span class="label">Area / Meja</span><span class="bold">{{ $selfOrder->serviceArea->name ?? '-' }}</span></div>
        @endif
        <div class="row"><span class="label">Bayar</span><span>{{ $selfOrder->payment_method_label }}</span></div>
        <div class="row"><span class="label">Waktu</span><span>{{ $selfOrder->created_at->format('d/m/Y H:i') }}</span></div>

        <div class="divider"></div>

        {{-- Items --}}
        @foreach($selfOrder->items as $item)
        <div style="margin-bottom: 4px;">
            <div class="row"><span class="bold">{{ $item->product_name }}</span></div>
            <div class="row label">
                <span>{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                <span>Rp {{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}</span>
            </div>
            @if($item->modifiers->isNotEmpty())
                @foreach($item->modifiers as $mod)
                <div class="row indent label">
                    <span>+ {{ $mod->modifier_name }}{{ ($mod->quantity ?? 1) > 1 ? " ×{$mod->quantity}" : '' }}</span>
                    @if($mod->price_adjustment > 0)
                    <span>Rp {{ number_format($mod->price_adjustment * ($mod->quantity ?? 1), 0, ',', '.') }}</span>
                    @endif
                </div>
                @endforeach
                <div class="row indent bold" style="border-top: 1px dotted #000;">
                    <span>Jumlah</span>
                    <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                </div>
            @endif
        </div>
        @endforeach

        <div class="divider"></div>

        {{-- Totals --}}
        <div class="row"><span>Subtotal</span><span>Rp {{ number_format($selfOrder->subtotal, 0, ',', '.') }}</span></div>
        @if($selfOrder->tax_amount > 0)
        <div class="row"><span>Pajak</span><span>Rp {{ number_format($selfOrder->tax_amount, 0, ',', '.') }}</span></div>
        @endif
        <div class="row bold" style="font-size: 1.1em; margin-top: 4px;">
            <span>TOTAL</span><span>Rp {{ number_format($selfOrder->total, 0, ',', '.') }}</span>
        </div>

        @if($selfOrder->notes)
        <div class="divider"></div>
        <p class="label">Catatan:</p>
        <p>{{ $selfOrder->notes }}</p>
        @endif

        <div class="divider"></div>
        <div class="center label">
            <p>{{ $receiptSettings['header_text'] ?? 'Terima kasih atas pesanan Anda!' }}</p>
            @if(!empty($receiptSettings['footer_text']))<p style="margin-top: 4px;">{{ $receiptSettings['footer_text'] }}</p>@endif
            <p style="margin-top: 4px;">Pantau: {{ url('/self-order/track/' . $selfOrder->order_token) }}</p>
        </div>
    </div>

    <div class="no-print">
        <button onclick="window.print()"
            style="background:#1d4ed8;color:#fff;padding:10px 24px;border:none;border-radius:8px;font-size:14px;font-weight:bold;cursor:pointer;">
            Cetak Struk
        </button>
    </div>

    <script>
        if (new URLSearchParams(window.location.search).get('auto_print') === '1') {
            window.onload = () => window.print();
        }
    </script>
</body>
</html>
