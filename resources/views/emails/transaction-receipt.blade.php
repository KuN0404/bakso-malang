<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #{{ $transaction->invoice_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin:0; padding:0; background:#f4f4f5; color:#1a1a2e; }
        .wrapper { max-width:520px; margin:32px auto; }
        .card { background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 24px rgba(0,0,0,.08); }
        .header { background:linear-gradient(135deg, #2563eb, #1d4ed8); padding:32px 24px; text-align:center; }
        .header h1 { color:#fff; font-size:22px; font-weight:800; margin:0 0 4px; }
        .header p { color:rgba(255,255,255,.8); font-size:13px; margin:0; }
        .body { padding:24px; }
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
        .total-row .total-price { color:#2563eb; }
        .receipt-btn { display:block; background:#2563eb; color:#fff; text-align:center; font-size:14px; font-weight:700; padding:14px 24px; border-radius:12px; text-decoration:none; margin-top:24px; }
        .footer { text-align:center; padding:20px 24px; border-top:1px solid #f0f0f0; color:#888; font-size:12px; }
        .footer strong { color:#2563eb; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="card">

        {{-- Header --}}
        <div class="header">
            <h1>{{ config('app.name', 'Resto') }}</h1>
            <p>Terima kasih atas kunjungan Anda!</p>
        </div>

        <div class="body">
            {{-- Info Grid --}}
            <div class="info-grid">
                <div class="info-item">
                    <div class="lbl">Invoice</div>
                    <div class="val">{{ $transaction->invoice_number }}</div>
                </div>
                <div class="info-item">
                    <div class="lbl">Tanggal</div>
                    <div class="val">{{ $transaction->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div class="info-item">
                    <div class="lbl">Tipe</div>
                    <div class="val">{{ $transaction->order_type === 'dine_in' ? 'Makan di Tempat' : 'Bawa Pulang' }}</div>
                </div>
                <div class="info-item">
                    <div class="lbl">Pembayaran</div>
                    <div class="val">{{ ucfirst($transaction->payment_method) }}</div>
                </div>
            </div>

            {{-- Items --}}
            <div class="section-title">Detail Pesanan</div>
            <table>
                @foreach($transaction->details as $detail)
                <tr>
                    <td>
                        <div class="item-name">{{ $detail->quantity }}× {{ $detail->product_name }}</div>
                        @if($detail->modifiers->isNotEmpty())
                        <div class="item-meta">{{ $detail->modifiers->pluck('name')->implode(', ') }}</div>
                        @endif
                    </td>
                    <td class="item-price">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach

                <tr>
                    <td style="border-bottom:none; padding-top:12px; color:#666;">Subtotal</td>
                    <td class="item-price" style="border-bottom:none; padding-top:12px; color:#666;">
                        Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}
                    </td>
                </tr>

                @if($transaction->tax_amount > 0)
                <tr>
                    <td style="border-bottom:none; color:#666;">Pajak</td>
                    <td class="item-price" style="border-bottom:none; color:#666;">
                        Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}
                    </td>
                </tr>
                @endif

                <tr class="total-row">
                    <td class="total-label">TOTAL</td>
                    <td class="item-price total-price">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                </tr>
            </table>

            {{-- Receipt Button --}}
            <a href="{{ route('receipt.show', ['token' => $transaction->receipt_token]) }}" class="receipt-btn">
                Lihat Struk →
            </a>
        </div>

        <div class="footer">
            <p>Email ini dikirim otomatis oleh <strong>{{ config('app.name') }}</strong></p>
            <p>Transaksi dibuat pada {{ $transaction->created_at->format('d M Y, H:i') }}</p>
        </div>

    </div>
</div>
</body>
</html>
