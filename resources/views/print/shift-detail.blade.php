@php
    $settings = \App\Models\Setting::getGroup('general');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Tutup Shift</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            margin: 0;
            padding: 10px;
            width: {{ $format === '58mm' ? '58mm' : '80mm' }};
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .border-t { border-top: 1px dashed #000; }
        .border-b { border-bottom: 1px dashed #000; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .mb-2 { margin-bottom: 8px; }
        .flex { display: flex; justify-content: space-between; }
        .w-full { width: 100%; }
        
        @media print {
            @page { margin: 0; size: auto; }
            body { margin: 0; padding: 5px; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center mb-2">
        <div class="font-bold" style="font-size: 14px; margin-bottom: 4px;">{{ $settings['store_name'] ?? 'Bakso Malang' }}</div>
        <div style="font-size: 10px; color: #666;">{{ $settings['store_address'] ?? '' }}</div>
        @if(!empty($settings['store_phone']))
        <div style="font-size: 10px; color: #666;">Telp: {{ $settings['store_phone'] }}</div>
        @endif
        <div class="border-b" style="margin-top: 8px; padding-bottom: 8px;">Laporan Tutup Shift</div>
    </div>

    <div class="border-t border-b py-1 mb-2">
        <div class="flex">
            <span>Kasir:</span>
            <span>{{ $shift->user->name }}</span>
        </div>
        <div class="flex">
            <span>Mulai:</span>
            <span>{{ $shift->started_at->format('d/m H:i') }}</span>
        </div>
        @if($shift->ended_at)
        <div class="flex">
            <span>Selesai:</span>
            <span>{{ $shift->ended_at->format('d/m H:i') }}</span>
        </div>
        @endif
    </div>

    <!-- SALES SUMMARY -->
    <div class="font-bold mb-1">REKAP PENJUALAN</div>
    @php
        $cashDetails = $shift->transactions->where('status', 'completed')->where('payment_method', 'cash');
        $cashCount = $cashDetails->count();
        $cashTotal = $cashDetails->sum('total');

        $nonCashDetails = $shift->transactions->where('status', 'completed')->where('payment_method', '!=', 'cash');
        $nonCashCount = $nonCashDetails->count();
        $nonCashTotal = $nonCashDetails->sum('total');
    @endphp

    <div class="flex">
        <span>Tunai ({{ $cashCount }})</span>
        <span class="text-right">{{ number_format($cashTotal, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Non-Tunai ({{ $nonCashCount }})</span>
        <span class="text-right">{{ number_format($nonCashTotal, 0, ',', '.') }}</span>
    </div>
    <div class="flex font-bold py-1 border-t mt-1">
        <span>TOTAL OMSET</span>
        <span class="text-right">{{ number_format($cashTotal + $nonCashTotal, 0, ',', '.') }}</span>
    </div>

    <!-- CASH REPORT -->
    <div class="font-bold mb-1 mt-2">LAPORAN KAS</div>
    <div class="flex">
        <span>Modal Awal</span>
        <span class="text-right">{{ number_format($shift->opening_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Penjualan Tunai</span>
        <span class="text-right">{{ number_format($cashTotal, 0, ',', '.') }}</span>
    </div>
    
    @if($shift->expenses->sum('amount') > 0)
    <div class="flex">
        <span>Pengeluaran (-)</span>
        <span class="text-right">{{ number_format($shift->expenses->sum('amount'), 0, ',', '.') }}</span>
    </div>
    <!-- Expenses Detail -->
    <div style="font-size: 10px; margin-left: 8px; color: #444;">
        @foreach($shift->expenses as $exp)
        <div class="flex">
            <span>- {{ $exp->description }}</span>
            <span>{{ number_format($exp->amount, 0, ',', '.') }}</span>
        </div>
        @endforeach
    </div>
    @endif

    <div class="flex font-bold py-1 border-t mt-1">
        <span>SALDO SEHARUSNYA</span>
        <span class="text-right">{{ number_format($shift->expected_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex">
        <span>Uang Fisik</span>
        <span class="text-right">{{ number_format($shift->actual_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex font-bold">
        <span>Selisih</span>
        <span class="text-right {{ $shift->cash_difference < 0 ? 'text-red-600' : '' }}">
            {{ number_format($shift->cash_difference, 0, ',', '.') }}
        </span>
    </div>

    @if($shift->close_notes)
    <div class="border-t py-1 mt-2">
        <div class="font-bold">Catatan:</div>
        <div>{{ $shift->close_notes }}</div>
    </div>
    @endif

    <div class="text-center mt-4">
        <div>Dicetak: {{ now()->format('d/m/Y H:i') }}</div>
        <div>--- Akhir Laporan ---</div>
    </div>
</body>
</html>
