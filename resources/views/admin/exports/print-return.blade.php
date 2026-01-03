<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Retur - {{ $return->return_number }}</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        .header p {
            margin: 2px 0;
        }
        .info {
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
        }
        td {
            padding: 5px 0;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .total-section {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
        @media print {
            body { 
                width: 100%; /* Adjust for actual paper width if needed */
            }
            @page {
                margin: 0;
                size: inherit;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h2>BAKSO MALANG</h2>
        <p>BUKTI RETUR BARANG</p>
    </div>

    <div class="info">
        <div class="info-row">
            <span>No. Retur</span>
            <span>{{ $return->return_number }}</span>
        </div>
        <div class="info-row">
            <span>Tanggal</span>
            <span>{{ $return->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span>Kasir</span>
            <span>{{ $return->user->name ?? '-' }}</span>
        </div>
        <div class="info-row">
            <span>Ref. Inv</span>
            <span>{{ $return->transaction->invoice_number ?? '-' }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="45%">Item</th>
                <th width="15%" class="text-right">Qty</th>
                <th width="40%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($return->items as $item)
            <tr>
                <td>{{ $item->product ? $item->product->name : 'Item Terhapus' }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="info-row">
            <b>TOTAL REFUND</b>
            <b>Rp {{ number_format($return->total_refund, 0, ',', '.') }}</b>
        </div>
    </div>

    <div style="margin-top: 10px; border-top: 1px dashed #000; padding-top: 5px;">
        <div><b>Alasan Retur:</b></div>
        <div>{{ $return->reason }}</div>
    </div>

    <div class="footer">
        <p>Simpan struk ini sebagai bukti retur.</p>
    </div>
</body>
</html>
