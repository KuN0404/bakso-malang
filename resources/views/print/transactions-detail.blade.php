<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Detail Transaksi</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header p { margin: 5px 0; color: #666; }
        
        .transaction-card { border: 1px solid #ddd; margin-bottom: 15px; page-break-inside: avoid; }
        .transaction-header { background-color: #f5f5f5; padding: 8px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .transaction-body { padding: 8px; }
        
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 4px; text-align: left; }
        .text-right { text-align: right; }
        
        .item-row td { border-bottom: 1px dashed #eee; }
        .modifier { font-size: 10px; color: #666; margin-left: 10px; }
        
        .grand-total { margin-top: 20px; text-align: right; font-size: 14px; border-top: 2px solid #333; padding-top: 10px; }
        
        @media print {
            @page { size: auto; margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>Laporan Detail Transaksi</h1>
        <p>Bakso Malang</p>
        <p>Periode: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
    </div>

    @foreach($transactions as $transaction)
    <div class="transaction-card">
        <div class="transaction-header">
            <span><strong>{{ $transaction->invoice_number }}</strong></span>
            <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="transaction-body">
            <div style="margin-bottom: 5px; font-size: 11px; color: #555;">
                Kasir: {{ $transaction->user?->name ?? '-' }} | Pembayaran: {{ $transaction->paymentSource?->name ?? 'Cash' }}
            </div>
            <table>
                <tbody>
                    @foreach($transaction->details as $detail)
                    <tr class="item-row">
                        <td width="50%">
                            {{ $detail->product->name }}
                            @if($detail->modifiers->count() > 0)
                                <div class="modifier">
                                    + {{ $detail->modifiers->pluck('name')->join(', ') }}
                                </div>
                            @endif
                        </td>
                        <td width="15%" class="text-center">{{ $detail->quantity }}x</td>
                        <td width="20%" class="text-right">{{ number_format($detail->price, 0, ',', '.') }}</td>
                        <td width="15%" class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="text-right" style="margin-top: 5px; font-weight: bold;">
                Total: Rp {{ number_format($transaction->total, 0, ',', '.') }}
            </div>
        </div>
    </div>
    @endforeach

    <div class="grand-total">
        <strong>Total Keseluruhan: Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong><br>
        <span style="font-size: 12px; font-weight: normal;">Total Transaksi: {{ number_format($summary['total_transactions']) }}</span>
    </div>
</body>
</html>
