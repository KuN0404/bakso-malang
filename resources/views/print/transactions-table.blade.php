<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f5f5f5; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; display: flex; gap: 20px; justify-content: flex-end; }
        .summary-item { border: 1px solid #ddd; padding: 10px; border-radius: 5px; }
        @media print {
            @page { size: auto; margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Laporan Transaksi</h1>
        <p>Bakso Malang</p>
        <p>Periode: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Invoice</th>
                <th>Waktu</th>
                <th>Kasir</th>
                <th>Pembayaran</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $transaction)
            <tr>
                <td>{{ $transaction->invoice_number }}</td>
                <td>{{ $transaction->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $transaction->user?->name ?? '-' }}</td>
                <td>{{ $transaction->paymentSource?->name ?? 'Cash' }}</td>
                <td class="text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right"><strong>Total Pendapatan</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div class="summary-item">
            <strong>Total Transaksi:</strong> {{ number_format($summary['total_transactions']) }}
        </div>
    </div>
</body>
</html>
