<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Retur - Cetak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style type="text/tailwindcss">
        @layer utilities {
            .print-break-inside-avoid {
                break-inside: avoid;
            }
        }
        @media print {
            @page {
                size: {{ $format == '58mm' ? '58mm auto' : ($format == '76mm' ? '76mm auto' : ($format == 'A5' ? 'A5 landscape' : 'A4 portrait')) }};
                margin: {{ $format == '58mm' || $format == '76mm' ? '0' : '10mm' }};
            }
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                width: {{ $format == '58mm' ? '58mm' : ($format == '76mm' ? '76mm' : '100%') }};
            }
        }
    </style>
</head>
<body class="bg-white p-8" onload="window.print()">
    <div class="w-full text-[10px] leading-tight {{ ($format == '58mm' || $format == '76mm') ? 'font-mono' : '' }}">
        @if($format == '58mm' || $format == '76mm')
             <!-- Struk / Receipt Layout -->
            <div class="text-center border-b border-dashed border-gray-300 pb-3 mb-3">
                <h1 class="text-base font-bold uppercase">REKAP RETUR</h1>
                <p class="text-[10px] text-gray-500 mt-1">{{ $start->translatedFormat('d/m/y') }} - {{ $end->translatedFormat('d/m/y') }}</p>
            </div>

            <div class="pb-3 mb-3 border-b border-dashed border-gray-300">
                @foreach($returns as $return)
                    <div class="mb-4 last:mb-0">
                        <div class="flex justify-between font-bold">
                            <span>{{ $return->return_number }}</span>
                            <span>{{ $return->created_at->format('d/m H:i') }}</span>
                        </div>
                        <div class="flex justify-between text-gray-500 mb-1 text-[10px]">
                             <span>{{ $return->transaction->invoice_number }}</span>
                             <span>{{ $return->user->name }}</span>
                        </div>
                        <div class="pl-2 border-l border-dashed border-gray-300 my-1">
                            @foreach($return->items as $item)
                                <div class="mb-1">
                                    <div class="truncate">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item') }}</div>
                                    <div class="flex justify-between text-gray-500">
                                        <span>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                                        <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                         <div class="text-right font-bold">
                            Total: Rp {{ number_format($return->total_refund, 0, ',', '.') }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="pt-2">
                 <div class="flex justify-between items-center font-bold text-sm">
                    <span>TOTAL RETUR</span>
                    <span>Rp {{ number_format($todayTotal, 0, ',', '.') }}</span>
                 </div>
                 <p class="mt-4 text-center text-[10px] text-gray-400">{{ now()->translatedFormat('d/m/Y H:i') }}</p>
            </div>

        @else
            <!-- Standard Table Layout (A4/A5) -->
            <!-- Header -->
            <div class="text-center mb-6">
                <h1 class="text-xl font-bold uppercase tracking-wider text-gray-900">Laporan Rekap Data Retur Produk</h1>
                <p class="text-sm font-bold uppercase text-gray-600 mt-1">Periode: {{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}</p>
            </div>

            <!-- Table -->
            <table class="w-full mb-6">
                 <thead class="border-y-2 border-gray-800">
                    <tr>
                        <th class="py-2 text-left text-xs font-bold uppercase text-gray-900 w-[5%]">No.</th>
                        <th class="py-2 text-left text-xs font-bold uppercase text-gray-900 w-[15%]">Tanggal</th>
                        <th class="py-2 text-left text-xs font-bold uppercase text-gray-900 w-[15%]">Invoice</th>
                        <th class="py-2 text-left text-xs font-bold uppercase text-gray-900 w-[15%]">Kasir</th>
                        <th class="py-2 text-left text-xs font-bold uppercase text-gray-900 w-[35%]">Detail Produk</th>
                        <th class="py-2 text-right text-xs font-bold uppercase text-gray-900 w-[15%]">Total</th>
                    </tr>
                </thead>
                <tbody class="text-xs text-gray-900">
                    @foreach($returns as $index => $return)
                        <tr class="border-b border-gray-100">
                            <td class="py-2 align-top">{{ $index + 1 }}</td>
                            <td class="py-2 align-top">{{ $return->created_at->format('d M Y H:i:s') }}</td>
                            <td class="py-2 align-top font-medium">{{ $return->transaction->invoice_number }}</td>
                            <td class="py-2 align-top lowercase">{{ $return->user->name }}</td>
                            <td class="py-2 align-top">
                                <ul class="list-none space-y-1">
                                    @foreach($return->items as $item)
                                        <li>
                                            <span class="font-semibold">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus') }}</span>
                                            @if(is_array($item->modifiers) && count($item->modifiers) > 0)
                                                <span class="text-[10px] text-gray-500 italic">
                                                    (+ {{ collect($item->modifiers)->pluck('name')->implode(', ') }})
                                                </span>
                                            @endif
                                            <span class="text-gray-500">({{ $item->quantity }})</span>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($return->reason)
                                    <div class="text-[10px] text-gray-500 italic mt-1">Alasan: "{{ $return->reason }}"</div>
                                @endif
                            </td>
                            <td class="py-2 align-top text-right font-bold">
                                {{ number_format($return->total_refund, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

             <!-- Summary Footer -->
             <div class="flex justify-end mt-4 page-break-inside-avoid">
                <div class="w-1/3 border-t-2 border-gray-800 pt-2">
                     <div class="flex justify-between items-center text-xs font-bold uppercase">
                        <span>Total Nilai Retur</span>
                        <span class="text-sm">Rp {{ number_format($todayTotal, 0, ',', '.') }}</span>
                     </div>
                </div>
            </div>
        @endif

        <div class="mt-8 text-center text-[10px] text-gray-400">
            Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
        </div>
    </div>
</body>
</html>
