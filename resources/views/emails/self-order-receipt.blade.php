<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Pesanan #{{ $selfOrder->queue_display }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin:0; padding:0; background:#f4f4f5; color:#1a1a2e; }
        .wrapper { max-width:520px; margin:32px auto; }
        .card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
        .header { background:linear-gradient(135deg, #dc2626, #b91c1c); padding:32px 24px; text-align:center; }
        .header h1 { color:#fff; font-size:22px; font-weight:800; margin:0 0 4px; }
        .header p { color:rgba(255,255,255,.8); font-size:13px; margin:0; }
        .queue-box { background:#fff3; border-radius:12px; display:inline-block; padding:12px 32px; margin-top:16px; }
        .queue-box .label { font-size:11px; color:rgba(255,255,255,.7); text-transform:uppercase; letter-spacing:.05em; }
        .queue-box .number { font-size:48px; font-weight:900; color:#fff; line-height:1; }
        .body { padding:24px; }
        .status-badge { display:inline-block; background:#dcfce7; color:#16a34a; font-size:12px; font-weight:700; padding:4px 14px; border-radius:99px; text-transform:uppercase; letter-spacing:.05em; }
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; margin:20px 0; }
        .info-item { background:#f8f8f8; border-radius:10px; padding:12px; }
        .info-item .lbl { font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.05em; margin-bottom:2px; }
        .info-item .val { font-size:14px; font-weight:700; color:#1a1a2e; }
        .section-title { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#888; margin:20px 0 8px; }
        table { width:100%; border-collapse:collapse; font-size:14px; }
        td { padding:8px 0; border-bottom:1px solid #f0f0f0; }
        .item-name { font-weight:600; }
        .item-meta { font-size:12px; color:#888; }
        .item-price { text-align:right; font-weight:700; }
        .total-row td { font-size:16px; font-weight:800; border-bottom:none; padding-top:12px; }
        .total-row .total-label { color:#1a1a2e; }
        .total-row .total-price { color:#dc2626; }
        .tracking-btn { display:block; background:#dc2626; color:#fff; text-align:center; font-size:14px; font-weight:700; padding:14px 24px; border-radius:12px; text-decoration:none; margin-top:24px; }
        .footer { text-align:center; padding:20px 24px; border-top:1px solid #f0f0f0; color:#888; font-size:12px; }
        .footer strong { color:#dc2626; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <h1>{{ config('app.name', 'Resto') }}</h1>
            <p>Terima kasih atas pesanan Anda!</p>
            <div class="queue-box">
                <div class="label">Nomor Antrian</div>
                <div class="number">{{ $selfOrder->queue_display }}</div>
            </div>
        </div>

        <div class="body">
            <div class="status-badge">{{ $selfOrder->status->label() }}</div>

            {{-- Info Grid --}}
            <div class="info-grid">
                <div class="info-item">
                    <div class="lbl">Nama</div>
                    <div class="val">{{ $selfOrder->customer_name }}</div>
                </div>
                <div class="info-item">
                    <div class="lbl">Pembayaran</div>
                    <div class="val">{{ $selfOrder->payment_method_label }}</div>
                </div>
                @if($selfOrder->invoice_number)
                <div class="info-item">
                    <div class="lbl">Invoice</div>
                    <div class="val">{{ $selfOrder->invoice_number }}</div>
                </div>
                @endif
                <div class="info-item">
                    <div class="lbl">Tipe Order</div>
                    <div class="val">{{ $selfOrder->order_type }}</div>
                </div>
            </div>

            {{-- Items --}}
            <div class="section-title">Detail Pesanan</div>
            <table>
                @foreach($selfOrder->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->quantity }}× {{ $item->product_name }}</div>
                        @if($item->modifiers->count())
                        <div class="item-meta">{{ $item->modifier_names }}</div>
                        @endif
                        @if($item->notes)
                        <div class="item-meta">Catatan: {{ $item->notes }}</div>
                        @endif
                    </td>
                    <td class="item-price">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                {{-- Subtotal --}}
                <tr>
                    <td style="border-bottom:none; padding-top:12px; color:#666;">Subtotal</td>
                    <td class="item-price" style="border-bottom:none; padding-top:12px; color:#666;">
                        Rp {{ number_format($selfOrder->subtotal, 0, ',', '.') }}
                    </td>
                </tr>

                @if($selfOrder->tax_amount > 0)
                <tr>
                    <td style="border-bottom:none; color:#666;">Pajak</td>
                    <td class="item-price" style="border-bottom:none; color:#666;">
                        Rp {{ number_format($selfOrder->tax_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @endif

                <tr class="total-row">
                    <td class="total-label">TOTAL</td>
                    <td class="item-price total-price">Rp {{ number_format($selfOrder->total, 0, ',', '.') }}</td>
                </tr>
            </table>

            @if($selfOrder->notes)
            <div style="margin-top:16px; background:#fef3c7; border-radius:10px; padding:12px;">
                <div style="font-size:11px; color:#92400e; text-transform:uppercase; font-weight:700; margin-bottom:4px;">Catatan Pesanan</div>
                <div style="font-size:14px; color:#78350f;">{{ $selfOrder->notes }}</div>
            </div>
            @endif

            {{-- Receipt Button --}}
            @if($selfOrder->transaction?->receipt_token)
            <a href="{{ route('receipt.show', ['token' => $selfOrder->transaction->receipt_token]) }}"
                class="tracking-btn">
                Lihat Struk →
            </a>
            @endif

            <a href="{{ route('self-order.tracking', ['token' => $selfOrder->order_token]) }}"
                style="display:block; text-align:center; font-size:13px; color:#888; margin-top:12px;">
                Pantau status pesanan
            </a>
        </div>

        <div class="footer">
            <p>Email ini dikirim otomatis oleh <strong>{{ config('app.name') }}</strong></p>
            <p>Pesanan dibuat pada {{ $selfOrder->created_at->format('d M Y, H:i') }}</p>
        </div>

    </div>
</div>
</body>
</html>
