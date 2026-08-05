@php
    $printerConfig = $printerConfig ?? ($this->printerConfig ?? null);
    $settings = $settings ?? ($this->generalSettings ?? []);
    
    $paperWidth = match($printerConfig?->paper_size ?? '58mm') {
        '58mm' => '58mm',
        '80mm' => '80mm',
        'custom' => ($printerConfig->paper_width_px ?? 200) . 'px',
        default => '58mm',
    };
@endphp

    #shift-receipt-content, #shift-receipt-content * {
        color: #000 !important;
        border-color: #000 !important;
    }
    @media print {
        @page {
            size: {{ $paperWidth }} auto;
            margin: 2mm;
        }
        #shift-receipt-content {
            width: {{ $paperWidth }};
            font-family: {{ $printerConfig?->font_family ?? 'monospace' }};
            font-size: {{ $printerConfig?->font_size_px ?? 12 }}px;
        }
    }
</style>

<div id="shift-receipt-content" class="font-mono text-xs animate-fade-in text-black" style="max-width: {{ $paperWidth }}; margin: 0 auto; color: #000 !important;">
    <!-- Header -->
    <div class="text-center mb-2 pb-2 border-b border-dashed border-black">
        <h1 class="font-bold text-base">{{ $settings['store_name'] ?? 'Bakso Malang' }}</h1>
        <p class="text-[10px]">{{ $settings['store_address'] ?? '' }}</p>
        @if(!empty($settings['store_phone']))
        <p class="text-[10px]">Telp: {{ $settings['store_phone'] }}</p>
        @endif
        <div class="font-bold mt-2" style="font-size: 11px;">LAPORAN TUTUP SHIFT</div>
    </div>

    <!-- Metadata -->
    <div class="border-b border-dashed border-gray-300 pb-2 mb-2">
        <div class="flex justify-between">
            <span>Kasir:</span>
            <span>{{ $shift->user->name }}</span>
        </div>
        <div class="flex justify-between">
            <span>Mulai:</span>
            <span>{{ $shift->started_at->format('d/m H:i') }}</span>
        </div>
        @if($shift->ended_at)
        <div class="flex justify-between">
            <span>Selesai:</span>
            <span>{{ $shift->ended_at->format('d/m H:i') }}</span>
        </div>
        @endif
    </div>

    <!-- Saldo Laporan -->
    @php
        $cashDetails = $shift->transactions->where('status', 'completed')->where('payment_method', 'cash');
        $cashCount = $cashDetails->count();
        $cashTotal = $cashDetails->sum('total');

        $nonCashDetails = $shift->transactions->where('status', 'completed')->where('payment_method', '!=', 'cash');
        $nonCashCount = $nonCashDetails->count();
        $nonCashTotal = $nonCashDetails->sum('total');
        
        $totalOmset = $cashTotal + $nonCashTotal;
        $totalTrx = $cashCount + $nonCashCount;
        $expenseTotal = $shift->expenses->sum('amount');
        
        // Expected cash in drawer = Modal Awal + Cash Sales - Expenses
        $expectedSaldo = $shift->opening_cash + $cashTotal - $expenseTotal;
        $cashDiff      = $shift->actual_cash !== null ? ($shift->actual_cash - $expectedSaldo) : null;
        $nonCashDiff   = $shift->non_cash_difference;
    @endphp

    <div class="font-bold mb-1">LAPORAN SALDO</div>
    <div class="flex justify-between">
        <span>Modal Awal:</span>
        <span>Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between">
        <span>Penjualan ({{ $totalTrx }} trx):</span>
        <span>Rp {{ number_format($totalOmset, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between text-gray-500 pl-2 text-[10px]">
        <span>Tunai:</span>
        <span>Rp {{ number_format($cashTotal, 0, ',', '.') }} ({{ $cashCount }} trx)</span>
    </div>
    <div class="flex justify-between text-gray-500 pl-2 text-[10px] pb-1">
        <span>Non-Tunai:</span>
        <span>Rp {{ number_format($nonCashTotal, 0, ',', '.') }} ({{ $nonCashCount }} trx)</span>
    </div>
    
    @if($expenseTotal > 0)
    <div class="flex justify-between">
        <span>Pengeluaran (-):</span>
        <span>Rp {{ number_format($expenseTotal, 0, ',', '.') }}</span>
    </div>
    @foreach($shift->expenses as $exp)
        <div class="flex justify-between text-gray-500 pl-2 text-[10px]">
            <span>- {{ $exp->description }}</span>
            <span>Rp {{ number_format($exp->amount, 0, ',', '.') }}</span>
        </div>
    @endforeach
    @endif

    <!-- Rekonsiliasi Tunai -->
    <div class="font-bold mt-2 mb-1 border-t border-dashed border-gray-300 pt-2">REKONSILIASI TUNAI</div>
    <div class="flex justify-between">
        <span>Modal Awal:</span>
        <span>Rp {{ number_format($shift->opening_cash, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between">
        <span>Penjualan Tunai:</span>
        <span>Rp {{ number_format($cashTotal, 0, ',', '.') }}</span>
    </div>
    @if($expenseTotal > 0)
    <div class="flex justify-between text-red-600">
        <span>Pengeluaran:</span>
        <span>-Rp {{ number_format($expenseTotal, 0, ',', '.') }}</span>
    </div>
    @endif
    <div class="flex justify-between font-bold border-t border-b border-dashed border-gray-300 py-1 my-1 bg-gray-50">
        <span>Ekspektasi Tunai:</span>
        <span>Rp {{ number_format($expectedSaldo, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between">
        <span>Fisik di Laci:</span>
        <span>Rp {{ number_format($shift->actual_cash, 0, ',', '.') }}</span>
    </div>
    @if($cashDiff !== null)
    <div class="flex justify-between font-bold {{ $cashDiff < 0 ? 'text-red-600' : 'text-green-600' }}">
        <span>Selisih Tunai:</span>
        <span>{{ ($cashDiff >= 0 ? '+' : '') }}Rp {{ number_format($cashDiff, 0, ',', '.') }}</span>
    </div>
    @endif

    <!-- Rekonsiliasi Non-Tunai -->
    @if($nonCashTotal > 0 || ($shift->actual_non_cash !== null))
    <div class="font-bold mt-2 mb-1 border-t border-dashed border-gray-300 pt-2">REKONSILIASI NON-TUNAI</div>
    <div class="flex justify-between">
        <span>Sistem ({{ $nonCashCount }} trx):</span>
        <span>Rp {{ number_format($shift->expected_non_cash ?? $nonCashTotal, 0, ',', '.') }}</span>
    </div>
    <div class="flex justify-between">
        <span>Terverifikasi Kasir:</span>
        <span>Rp {{ number_format($shift->actual_non_cash ?? 0, 0, ',', '.') }}</span>
    </div>
    @if($nonCashDiff !== null)
    <div class="flex justify-between font-bold {{ $nonCashDiff < 0 ? 'text-red-600' : 'text-green-600' }}">
        <span>Selisih Non-Tunai:</span>
        <span>{{ ($nonCashDiff >= 0 ? '+' : '') }}Rp {{ number_format($nonCashDiff, 0, ',', '.') }}</span>
    </div>
    @endif
    @endif

    @if($shift->notes)
    <div class="border-t border-dashed border-gray-300 py-1 mt-2">
        <div class="font-bold">Catatan:</div>
        <div class="text-gray-600 italic">{{ $shift->notes }}</div>
    </div>
    @endif

    <div class="text-center mt-4 pt-2 border-t border-dashed border-gray-300 text-[10px] text-gray-500">
        <div>Dicetak: {{ now()->format('d/m/Y H:i:s') }}</div>
        <div class="font-bold mt-1">--- Akhir Laporan ---</div>
    </div>
</div>
