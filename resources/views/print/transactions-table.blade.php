@php
    $settings = \App\Models\Setting::getGroup('general');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Transaksi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            @page {
                size: {{ $format == '58mm' ? '58mm auto' : ($format == '76mm' ? '76mm auto' : ($format == 'A5' ? 'A5 landscape' : 'A4 portrait')) }};
                margin: {{ $format == '58mm' || $format == '76mm' ? '2mm' : '10mm' }};
            }
        }
        /* Common Styles */
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        
        /* A4 Specific Styles */
        .a4-font { font-family: sans-serif; }
        .a4-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .a4-table th, .a4-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .a4-table th { background-color: #f5f5f5; }
    </style>
</head>
<body class="bg-white {{ ($format == '58mm' || $format == '76mm') ? 'p-1' : 'p-8 a4-font' }}" onload="window.print()">
    @if($format == '58mm' || $format == '76mm')
        <div class="text-xs font-mono max-w-[{{ $format }}]">
             <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
                <h1 class="text-sm font-bold uppercase">Laporan Transaksi</h1>
                <p class="text-[10px] mt-1">{{ $start->format('d/m/y') }} - {{ $end->format('d/m/y') }}</p>
             </div>
             
             @foreach($transactions as $transaction)
                <div class="border-b border-dashed border-gray-300 mb-2 pb-2">
                    <div class="flex justify-between font-bold">
                        <span>{{ $transaction->invoice_number }}</span>
                        <span>{{ $transaction->created_at->format('d/m H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-[10px]">
                        <span>{{ $transaction->user?->name ?? '-' }}</span>
                        <span>{{ $transaction->paymentSource?->name ?? 'Cash' }}</span>
                    </div>
                    <div class="text-right font-bold mt-1">
                        Rp {{ number_format($transaction->total, 0, ',', '.') }}
                    </div>
                </div>
             @endforeach

             <div class="flex justify-between font-bold border-t border-dashed border-gray-400 pt-2 mt-2">
                 <span>TOTAL PENDAPATAN</span>
                 <span>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</span>
             </div>
             <div class="text-center text-[10px] text-gray-400 mt-4">
                 {{ $summary['total_transactions'] }} Transaksi Terdaftar
             </div>
        </div>
    @else
        <!-- A4 Layout (Existing Table Structure) -->
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold">Laporan Transaksi</h1>
            <p class="text-gray-600">{{ $settings['store_name'] ?? 'Bakso Malang' }}</p>
            <p class="text-sm text-gray-500">Periode: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
        </div>

        <table class="a4-table">
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

        <div class="flex justify-end mt-4 gap-4">
            <div class="border border-gray-200 p-2 rounded bg-gray-50">
                 <strong>Total Transaksi:</strong> {{ number_format($summary['total_transactions']) }}
            </div>
        </div>
    @endif
</body>
</html>
