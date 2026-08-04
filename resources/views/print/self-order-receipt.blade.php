<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Struk Self Order #{{ $selfOrder->queue_display }}</title>
    @php
        $printerConfig = \App\Models\PrinterConfig::getDefault();
        $settings = \App\Models\Setting::getGroup('general');
        $receiptSettings = \App\Models\Setting::getGroup('receipt');

        $paperWidth = match($printerConfig?->paper_size ?? '58mm') {
            '58mm'   => '58mm',
            '80mm'   => '80mm',
            'custom' => ($printerConfig->paper_width_px ?? 200) . 'px',
            default  => '58mm',
        };
        $fontFamily = $printerConfig?->font_family ?? 'monospace';
        $fontSize   = $printerConfig?->font_size_px ?? 12;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: {{ $fontFamily }}; font-size: {{ $fontSize }}px; background: #f3f4f6; }
        #receipt-content { width: {{ $paperWidth }}; margin: 16px auto; background: #fff; padding: 10px; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .divider { border-top: 1px dashed #999; margin: 8px 0; }
        .row { display: flex; justify-content: space-between; gap: 8px; }
        .row.indent { padding-left: 10px; }
        .label { color: #666; font-size: 0.85em; }
        .store-name { font-size: 1.2em; font-weight: bold; text-transform: uppercase; }
        .queue { font-size: 2.6em; font-weight: 900; line-height: 1; }
        .no-print { text-align: center; margin-top: 16px; }
        @media print {
            body { background: #fff; }
            #receipt-content { width: {{ $paperWidth }}; margin: 0; padding: 2mm; }
            .no-print { display: none; }
            @page { size: {{ $paperWidth }} auto; margin: 2mm; }
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
        <div class="row"><span class="label">Tipe</span><span>{{ ucfirst(str_replace('_', ' ', $selfOrder->order_type)) }}</span></div>
        <div class="row"><span class="label">Bayar</span><span>{{ $selfOrder->payment_method_label }}</span></div>
        <div class="row"><span class="label">Waktu</span><span>{{ $selfOrder->created_at->format('d/m/Y H:i') }}</span></div>

        <div class="divider"></div>

        {{-- Items --}}
        @foreach($selfOrder->items as $item)
        <div style="margin-bottom: 6px;">
            <div class="row"><span class="bold">{{ $item->product_name }}</span></div>
            <div class="row label">
                <span>{{ $item->quantity }} x Rp {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
            @foreach($item->modifiers as $mod)
            <div class="row indent label">
                <span>+ {{ $mod->modifier_name }}</span>
                @if($mod->price_adjustment > 0)
                <span>Rp {{ number_format($mod->price_adjustment * $mod->quantity, 0, ',', '.') }}</span>
                @endif
            </div>
            @endforeach
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
