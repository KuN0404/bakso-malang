<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - {{ $start->format('d/m/Y') }}</title>
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
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        .a4-font { font-family: system-ui, -apple-system, sans-serif; }
    </style>
</head>
<body class="bg-white {{ ($format == '58mm' || $format == '76mm') ? 'p-1' : 'p-8' }}" onload="window.print()">
    <!-- Struk / A4 Logic -->
    <div class="{{ ($format == '58mm' || $format == '76mm') ? 'font-mono text-xs max-w-['.$format.']' : 'w-full max-w-4xl mx-auto a4-font text-sm' }}">
        
        <!-- Header -->
        <div class="text-center border-b border-dashed border-gray-300 pb-3 mb-3">
            <h1 class="{{ ($format == '58mm' || $format == '76mm') ? 'text-base' : 'text-xl' }} font-bold uppercase">Laporan Analisis & Performa</h1>
            <p class="text-[10px] text-gray-500 mt-1">{{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}</p>
        </div>

        <!-- Summary -->
        <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
            <h3 class="font-bold mb-2 uppercase text-gray-400 text-[10px]">Ringkasan</h3>
            <div class="flex justify-between font-bold text-base mb-1">
                <span>Total Omset</span>
                <span>Rp {{ number_format($summary['total_sales'], 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>Transaksi Selesai</span>
                <span>{{ $summary['completed_count'] }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                 <span>Rata-rata / Trx</span>
                 <span>Rp {{ number_format($summary['average_transaction'], 0, ',', '.') }}</span>
            </div>
        </div>

        <!-- Payment Breakdown -->
         <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
            <h3 class="font-bold mb-2 uppercase text-gray-400 text-[10px]">Metode Pembayaran</h3>
            @foreach($payments as $payment)
                <div class="flex justify-between mb-1">
                    <span class="capitalize">{{ $payment->payment_method }}</span>
                    <span>Rp {{ number_format($payment->total_amount, 0, ',', '.') }}</span>
                </div>
            @endforeach
        </div>

        <!-- Category Breakdown -->
        <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
             <h3 class="font-bold mb-2 uppercase text-gray-400 text-[10px]">Penjualan per Kategori</h3>
             @foreach($categories as $cat)
                <div class="flex justify-between mb-1">
                    <span class="truncate w-1/2">{{ $cat->category_name }}</span>
                    <div class="text-right">
                        <div>Rp {{ number_format($cat->total_sales, 0, ',', '.') }}</div>
                        <div class="text-[10px] text-gray-400">{{ $cat->total_quantity }} item</div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Top Products -->
        <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
             <h3 class="font-bold mb-2 uppercase text-gray-400 text-[10px]">Top 10 Produk</h3>
             @foreach($topProducts as $idx => $prod)
                <div class="mb-2">
                    <div class="flex justify-between font-bold">
                        <span>{{ $idx + 1 }}. {{ $prod->product_name }}</span>
                    </div>
                    <div class="flex justify-between text-gray-500 text-[11px]">
                         <span>{{ $prod->total_quantity }} Terjual</span>
                         <span>Rp {{ number_format($prod->total_sales, 0, ',', '.') }}</span>
                    </div>
                </div>
             @endforeach
        </div>

        <!-- Footer -->
         <div class="text-center text-[10px] text-gray-400 mt-4">
             <p>Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}</p>
         </div>

    </div>
</body>
</html>
