<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift</title>
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
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .text-red-500 { color: #ef4444; }
        .text-green-500 { color: #22c55e; }
    </style>
</head>
<body class="bg-white {{ ($format == '58mm' || $format == '76mm') ? 'p-1' : 'p-8 a4-font' }}" onload="window.print()">
    @if($format == '58mm' || $format == '76mm')
        <div class="text-xs font-mono max-w-[{{ $format }}]">
             <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
                <h1 class="text-sm font-bold uppercase">Laporan Shift</h1>
                <p class="text-[10px] mt-1">{{ $start->format('d/m/y') }} - {{ $end->format('d/m/y') }}</p>
             </div>
             
             @foreach($shifts as $shift)
                @php
                    $sales = $shift->transactions->where('status', 'completed')->sum('total');
                    $expenses = $shift->expenses->sum('amount');
                @endphp
                <div class="border-b border-dashed border-gray-300 mb-2 pb-2">
                    <div class="flex justify-between font-bold">
                        <span>{{ $shift->user->name }}</span>
                        <span>{{ $shift->started_at->format('d/m H:i') }}</span>
                    </div>
                    <div class="flex justify-between text-[10px] text-gray-500">
                         <span>Sales: {{ number_format($sales, 0, ',', '.') }}</span>
                         <span>Exp: {{ number_format($expenses, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between font-bold mt-1">
                        <span>Selisih:</span>
                        <span class="{{ $shift->cash_difference < 0 ? 'text-red-500' : 'text-green-500' }}">
                            {{ $shift->cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
             @endforeach

             <div class="border-t border-dashed border-gray-400 pt-2 mt-2">
                 <div class="flex justify-between font-bold text-[10px]">
                     <span>TOTAL SALES</span>
                     <span>Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</span>
                 </div>
                 <div class="flex justify-between font-bold text-[10px]">
                     <span>TOTAL EXPENSES</span>
                     <span>Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</span>
                 </div>
                 <div class="flex justify-between font-bold text-[10px] mt-1 border-t border-dashed border-gray-300 pt-1">
                     <span>TOTAL SELISIH</span>
                     <span class="{{ $summary['total_difference'] < 0 ? 'text-red-500' : 'text-green-500' }}">
                         Rp {{ number_format($summary['total_difference'], 0, ',', '.') }}
                     </span>
                 </div>
             </div>
        </div>
    @else
        <!-- A4 Layout -->
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold">Laporan Shift</h1>
            <p class="text-gray-600">Bakso Malang</p>
            <p class="text-sm text-gray-500">Periode: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
        </div>

        <table class="a4-table">
            <thead>
                <tr>
                    <th>Kasir</th>
                    <th>Mulai</th>
                    <th>Selesai</th>
                    <th class="text-right">Modal Awal</th>
                    <th class="text-right">Penjualan</th>
                    <th class="text-right">Pengeluaran</th>
                    <th class="text-right">Selisih</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($shifts as $shift)
                @php
                    $sales = $shift->transactions->where('status', 'completed')->sum('total');
                    $expenses = $shift->expenses->sum('amount');
                @endphp
                <tr>
                    <td>{{ $shift->user->name }}</td>
                    <td>{{ $shift->started_at->format('d/m/H:i') }}</td>
                    <td>{{ $shift->ended_at ? $shift->ended_at->format('d/m/H:i') : '-' }}</td>
                    <td class="text-right">Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</td>
                    <td class="text-right text-green-500">Rp {{ number_format($sales, 0, ',', '.') }}</td>
                    <td class="text-right text-red-500">Rp {{ number_format($expenses, 0, ',', '.') }}</td>
                    <td class="text-right font-bold {{ $shift->cash_difference < 0 ? 'text-red-500' : 'text-green-500' }}">
                        {{ $shift->cash_difference >= 0 ? '+' : '' }}Rp {{ number_format($shift->cash_difference, 0, ',', '.') }}
                    </td>
                    <td>{{ $shift->status == 'open' ? 'Aktif' : 'Ditutup' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                    <td class="text-right text-green-500"><strong>Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</strong></td>
                    <td class="text-right text-red-500"><strong>Rp {{ number_format($summary['total_expenses'], 0, ',', '.') }}</strong></td>
                    <td class="text-right"><strong>Rp {{ number_format($summary['total_difference'], 0, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        <div class="flex justify-end mt-4 gap-4">
            <div class="border border-gray-200 p-2 rounded bg-gray-50 text-sm">
                 <strong>Total Shift:</strong> {{ number_format($summary['total_shifts']) }}
            </div>
        </div>
    @endif
</body>
</html>
