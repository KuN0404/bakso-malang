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
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-white p-8" onload="window.print()">
    <div class="max-w-4xl mx-auto">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Laporan Retur Produk</h1>
            <p class="text-gray-600">Bakso Malang</p>
            <p class="text-gray-500 text-sm mt-1">
                Periode: {{ $start->translatedFormat('d F Y') }} - {{ $end->translatedFormat('d F Y') }}
            </p>
        </div>

        <table class="w-full mb-8 border-collapse border border-gray-300 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border border-gray-300 p-2 text-left font-semibold text-gray-700 w-32">No. Retur</th>
                    <th class="border border-gray-300 p-2 text-left font-semibold text-gray-700 w-40">Invoice / Kasir</th>
                    <th class="border border-gray-300 p-2 text-left font-semibold text-gray-700">Detail Item</th>
                    <th class="border border-gray-300 p-2 text-left font-semibold text-gray-700 w-32">Alasan</th>
                    <th class="border border-gray-300 p-2 text-right font-semibold text-gray-700 w-32">Total Refund</th>
                    <th class="border border-gray-300 p-2 text-right font-semibold text-gray-700 w-32">Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach($returns as $return)
                    <tr class="print-break-inside-avoid">
                        <td class="border border-gray-300 p-2 align-top text-gray-800 font-medium">
                            {{ $return->return_number }}
                        </td>
                        <td class="border border-gray-300 p-2 align-top">
                            <div class="text-gray-900 font-medium">{{ $return->transaction->invoice_number }}</div>
                            <div class="text-xs text-gray-500 mt-0.5">{{ $return->user->name }}</div>
                        </td>
                        <td class="border border-gray-300 p-2 align-top">
                            <ul class="list-disc list-inside text-xs space-y-1 text-gray-700">
                                @foreach($return->items as $item)
                                    <li>
                                        <span class="font-medium text-gray-900">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus') }}</span>
                                        @if(is_array($item->modifiers) && count($item->modifiers) > 0)
                                            <span class="text-xs text-gray-500 italic ml-1">
                                                (+ {{ collect($item->modifiers)->pluck('name')->implode(', ') }})
                                            </span>
                                        @endif
                                        <span class="text-gray-500 ml-1">({{ $item->quantity }}x)</span>
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="border border-gray-300 p-2 align-top text-gray-600 italic">
                            {{ $return->reason }}
                        </td>
                        <td class="border border-gray-300 p-2 align-top text-right font-bold text-red-600">
                            Rp {{ number_format($return->total_refund, 0, ',', '.') }}
                        </td>
                        <td class="border border-gray-300 p-2 align-top text-right text-gray-600 text-xs">
                            {{ $return->created_at->format('d/m/Y') }}<br>
                            {{ $return->created_at->format('H:i') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

         <!-- Summary Footer -->
         <div class="flex justify-end mt-8 page-break-inside-avoid">
            <div class="w-1/2 border-t-2 border-gray-800 pt-4">
                 <div class="grid grid-cols-2 gap-4 text-right mb-4">
                    <div>
                       <p class="text-xs uppercase text-gray-500 font-bold tracking-wider">Total Transaksi</p>
                       <p class="text-xl font-bold text-gray-800">{{ $returnsCount }}</p>
                    </div>
                    <div>
                       <p class="text-xs uppercase text-gray-500 font-bold tracking-wider">Total Item</p>
                       <p class="text-xl font-bold text-gray-800">{{ $returnsQty }}</p>
                    </div>
                 </div>
                 <div class="border-t border-gray-200 pt-4 flex justify-between items-center bg-gray-50 p-3 rounded-lg print:bg-gray-50">
                    <span class="font-bold text-gray-800 uppercase text-sm">Total Nilai Retur</span>
                    <span class="text-2xl font-black text-red-600">Rp {{ number_format($todayTotal, 0, ',', '.') }}</span>
                 </div>
            </div>
        </div>

        <div class="mt-12 text-center text-xs text-gray-400">
            Dicetak pada: {{ now()->translatedFormat('d F Y H:i') }}
        </div>
    </div>
</body>
</html>
