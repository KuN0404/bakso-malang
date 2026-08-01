<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Inventori & Produksi - {{ $start->format('d/m/Y') }} s/d {{ $end->format('d/m/Y') }}</title>
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
    <div class="{{ ($format == '58mm' || $format == '76mm') ? 'font-mono text-xs max-w-['.$format.']' : 'w-full max-w-4xl mx-auto a4-font text-sm' }}">
        
        <!-- Header -->
        <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-4">
            <h1 class="{{ ($format == '58mm' || $format == '76mm') ? 'text-base' : 'text-xl' }} font-bold uppercase">Laporan Inventori & Produksi</h1>
            <p class="text-xs text-gray-600 mt-1">Periode: {{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}</p>
        </div>

        <!-- Summary -->
        <div class="border-b border-dashed border-gray-400 pb-3 mb-4">
            <h3 class="font-bold mb-2 uppercase text-gray-500 text-xs">Ringkasan Stat</h3>
            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <span class="text-gray-600">Nilai Aset Bahan:</span>
                    <span class="font-bold">Rp {{ number_format($totalAssetValue, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <span class="text-gray-600">Bahan Stok Kritis:</span>
                    <span class="font-bold text-red-600">{{ $criticalStockCount }} Bahan</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <span class="text-gray-600">Total Pembelian:</span>
                    <span class="font-bold">Rp {{ number_format($purchases->sum('total_amount'), 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 py-1">
                    <span class="text-gray-600">Total Produksi Dapur:</span>
                    <span class="font-bold">Rp {{ number_format($productions->sum('total_cost'), 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Content according to selected Tab -->
        @if($tab === 'valuation')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">1. Stok & Valuasi Aset Bahan Baku</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Kode</th>
                            <th class="py-1.5 px-2">Nama Bahan</th>
                            <th class="py-1.5 px-2 text-right">Stok Saat Ini</th>
                            <th class="py-1.5 px-2 text-right">HPP Satuan</th>
                            <th class="py-1.5 px-2 text-right">Total Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($ingredients as $ing)
                            <tr>
                                <td class="py-1.5 px-2 font-mono">{{ $ing->code }}</td>
                                <td class="py-1.5 px-2 font-medium">{{ $ing->name }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ number_format($ing->stock, 2, ',', '.') }} {{ $ing->unit }}</td>
                                <td class="py-1.5 px-2 text-right">Rp {{ number_format($ing->cost_price, 0, ',', '.') }}</td>
                                <td class="py-1.5 px-2 text-right font-extrabold">Rp {{ number_format($ing->stock * $ing->cost_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'components')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">2. Stok & Valuasi Aset Komponen Setengah Jadi</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Kode</th>
                            <th class="py-1.5 px-2">Nama Komponen</th>
                            <th class="py-1.5 px-2 text-right">Stok Saat Ini</th>
                            <th class="py-1.5 px-2 text-right">HPP Satuan</th>
                            <th class="py-1.5 px-2 text-right">Total Nilai Aset</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($components as $comp)
                            <tr>
                                <td class="py-1.5 px-2 font-mono">{{ $comp->code }}</td>
                                <td class="py-1.5 px-2 font-medium">{{ $comp->name }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ number_format($comp->stock, 2, ',', '.') }} {{ $comp->unit }}</td>
                                <td class="py-1.5 px-2 text-right">Rp {{ number_format($comp->cost_price, 0, ',', '.') }}</td>
                                <td class="py-1.5 px-2 text-right font-extrabold">Rp {{ number_format($comp->stock * $comp->cost_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'purchases')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">3. Riwayat Pembelian Stok</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Faktur</th>
                            <th class="py-1.5 px-2">Tanggal</th>
                            <th class="py-1.5 px-2">Supplier</th>
                            <th class="py-1.5 px-2 text-right">Total Nominal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($purchases as $p)
                            <tr>
                                <td class="py-1.5 px-2 font-mono">{{ $p->invoice_number }}</td>
                                <td class="py-1.5 px-2">{{ $p->purchase_date->format('d/m/Y') }}</td>
                                <td class="py-1.5 px-2">{{ $p->supplier_name ?: '-' }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">Rp {{ number_format($p->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'productions')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">4. Riwayat Batch Repacking Produksi</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Batch</th>
                            <th class="py-1.5 px-2">Tanggal</th>
                            <th class="py-1.5 px-2">Bahan Terpakai</th>
                            <th class="py-1.5 px-2">Hasil Komponen / Produk</th>
                            <th class="py-1.5 px-2 text-right">Biaya Dapur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($productions as $prod)
                            <tr>
                                <td class="py-1.5 px-2 font-mono">{{ $prod->production_code }}</td>
                                <td class="py-1.5 px-2">{{ $prod->production_date->format('d/m/Y') }}</td>
                                <td class="py-1.5 px-2">
                                    @foreach($prod->inputs as $in)
                                        <div>{{ $in->ingredient?->name }}: {{ number_format($in->quantity, 2, ',', '.') }} {{ $in->ingredient?->unit }}</div>
                                    @endforeach
                                </td>
                                <td class="py-1.5 px-2 font-medium">
                                    @foreach($prod->outputs as $out)
                                        <div>{{ $out->getOutputName() }}: {{ number_format($out->quantity, 0) }} {{ $out->getOutputUnit() }}</div>
                                    @endforeach
                                </td>
                                <td class="py-1.5 px-2 text-right font-bold">Rp {{ number_format($prod->total_cost, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'stock_logs')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">5. Mutasi & History Stok Bahan</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Waktu</th>
                            <th class="py-1.5 px-2">Bahan</th>
                            <th class="py-1.5 px-2">Tipe Mutasi</th>
                            <th class="py-1.5 px-2 text-right">Perubahan Qty</th>
                            <th class="py-1.5 px-2 text-right">Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stockLogs as $log)
                            <tr>
                                <td class="py-1.5 px-2">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-1.5 px-2 font-medium">{{ $log->ingredient?->name }}</td>
                                <td class="py-1.5 px-2 font-semibold">{{ strtoupper($log->type) }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ $log->type === 'production_use' || $log->type === 'sub' ? '-' : '+' }}{{ number_format($log->amount, 2, ',', '.') }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ number_format($log->final_stock, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @elseif($tab === 'component_stock_logs')
            <div class="mb-4">
                <h3 class="font-bold uppercase text-xs text-gray-700 mb-2">6. Mutasi & History Stok Komponen</h3>
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="border-b border-gray-400 font-bold bg-gray-50">
                            <th class="py-1.5 px-2">Waktu</th>
                            <th class="py-1.5 px-2">Komponen</th>
                            <th class="py-1.5 px-2">Tipe Mutasi</th>
                            <th class="py-1.5 px-2 text-right">Perubahan Qty</th>
                            <th class="py-1.5 px-2 text-right">Stok Akhir</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($componentStockLogs as $log)
                            <tr>
                                <td class="py-1.5 px-2">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-1.5 px-2 font-medium">{{ $log->component?->name }}</td>
                                <td class="py-1.5 px-2 font-semibold">{{ strtoupper($log->type) }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ $log->amount > 0 ? '+' : '' }}{{ number_format($log->amount, 2, ',', '.') }} {{ $log->component?->unit }}</td>
                                <td class="py-1.5 px-2 text-right font-bold">{{ number_format($log->final_stock, 2, ',', '.') }} {{ $log->component?->unit }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- Signature Footer -->
        <div class="mt-8 pt-4 border-t border-gray-300 flex justify-between text-xs text-gray-600">
            <div>
                <p>Dicetak Pada: {{ now()->format('d/m/Y H:i:s') }}</p>
                <p>Oleh: {{ Auth::user()?->name ?? 'System' }}</p>
            </div>
            <div class="text-center">
                <p class="mb-8">Mengetahui / Penanggung Jawab</p>
                <p class="font-bold">( .................................... )</p>
            </div>
        </div>
    </div>
</body>
</html>
