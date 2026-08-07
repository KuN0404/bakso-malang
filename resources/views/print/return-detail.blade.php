@php
    $settings = \App\Models\Setting::getGroup('general');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Retur - {{ $return->return_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none; }
            @page {
                size: {{ $format == '58mm' ? '58mm auto' : ($format == '80mm' ? '80mm auto' : ($format == 'A5' ? 'A5 landscape' : 'A4 portrait')) }};
                margin: {{ in_array($format, ['58mm', '80mm']) ? '0' : '10mm' }};
            }
        }
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        .a4-font { font-family: system-ui, -apple-system, sans-serif; }
        #receipt-content, #receipt-content * {
            color: #000 !important;
            border-color: #000 !important;
        }
        #receipt-content {
            box-sizing: border-box !important;
            overflow: hidden !important;
        }
        #receipt-content, #receipt-content * {
            max-width: 100%;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        #receipt-content .flex {
            width: 100%;
            overflow: hidden;
        }
        #receipt-content .flex > * {
            min-width: 0;
            flex-shrink: 1;
        }
    </style>
</head>
<body class="bg-white {{ in_array($format, ['58mm', '80mm']) ? 'p-1' : 'p-8' }}" onload="window.print()">
    @if(in_array($format, ['58mm', '80mm']))
        <div id="receipt-content" class="font-mono text-xs" style="max-width: {{ $format }}; width: {{ $format }};">
            <!-- Header -->
            <div class="text-left border-b border-dashed border-black pb-1.5 mb-1.5">
                <h1 class="text-base font-bold">{{ strtoupper($settings['store_name'] ?? 'BAKSO MALANG') }}</h1>
                <p class="text-xs">{{ $settings['store_address'] ?? '' }}</p>
                @if(!empty($settings['store_phone']))
                <p class="text-xs">Telp: {{ $settings['store_phone'] }}</p>
                @endif
                <h2 class="font-bold mt-1">BUKTI RETUR</h2>
            </div>

            <!-- Meta -->
            <div class="border-b border-dashed border-black pb-1.5 mb-1.5 text-xs">
                <div class="flex justify-between">
                    <span>No. Retur:</span>
                    <span class="font-bold">{{ $return->return_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Tanggal:</span>
                    <span>{{ $return->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span>
                    <span>{{ $return->user->name }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Ref. Inv:</span>
                    <span>{{ $return->transaction->invoice_number }}</span>
                </div>
            </div>

            <!-- Items -->
            <div class="border-b border-dashed border-black pb-1.5 mb-1.5">
                @foreach($return->items as $item)
                    <div class="mb-1">
                        <div class="font-bold">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus') }}</div>
                        <div class="flex justify-between pl-2">
                            <span>{{ $item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}</span>
                            <span>{{ number_format($item->unit_price * $item->quantity, 0, ',', '.') }}</span>
                        </div>
                        @if(is_array($item->modifiers) && count($item->modifiers) > 0)
                            @foreach($item->modifiers as $mod)
                                <div class="flex justify-between pl-4 text-[11px]">
                                    <span>+ {{ $mod['name'] ?? '-' }}</span>
                                    @if(($mod['price'] ?? 0) > 0)
                                        <span>{{ number_format($mod['price'], 0, ',', '.') }}</span>
                                    @endif
                                </div>
                            @endforeach
                            <div class="flex justify-between pl-2 font-semibold border-t border-dotted border-black">
                                <span>Jumlah</span>
                                <span>{{ number_format($item->subtotal, 0, ',', '.') }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Total -->
            <div class="border-b border-dashed border-black pb-1.5 mb-1.5">
                <div class="flex justify-between font-bold text-sm">
                    <span>TOTAL REFUND</span>
                    <span>Rp {{ number_format($return->total_refund, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center text-xs mb-2">
                <p class="italic mb-1">Alasan: "{{ $return->reason }}"</p>
                <p>Simpan struk ini sebagai bukti pengembalian.</p>
                <p class="mt-1 text-[10px]">{{ now()->format('d/m/Y H:i:s') }}</p>
            </div>
        </div>
    @else
        <div class="w-full max-w-4xl mx-auto a4-font">
            <!-- A4 Invoice Layout -->
            <div class="flex justify-between items-start mb-8 border-b-2 border-gray-800 pb-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ strtoupper($settings['store_name'] ?? 'BAKSO MALANG') }}</h1>
                    <p class="text-sm text-gray-600">{{ $settings['store_address'] ?? '' }}</p>
                    @if(!empty($settings['store_phone']))
                    <p class="text-sm text-gray-600">Telp: {{ $settings['store_phone'] }}</p>
                    @endif
                </div>
                <div class="text-right">
                    <h2 class="text-xl font-bold text-gray-800 uppercase tracking-widest">Nota Retur</h2>
                    <p class="text-sm font-medium text-gray-500 mt-1">#{{ $return->return_number }}</p>
                    <p class="text-sm text-gray-500">{{ $return->created_at->format('d F Y') }}</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                     <h3 class="font-bold text-sm uppercase text-gray-500 mb-2">Kasir</h3>
                     <p class="font-bold text-gray-800">{{ $return->user->name }}</p>
                </div>
                <div class="text-right">
                     <h3 class="font-bold text-sm uppercase text-gray-500 mb-2">Referensi Invoice</h3>
                     <p class="font-bold text-gray-800">{{ $return->transaction->invoice_number }}</p>
                </div>
            </div>

            <table class="w-full mb-8">
                <thead class="border-y-2 border-gray-800">
                    <tr>
                         <th class="py-2 text-left text-sm uppercase font-bold text-gray-900">Deskripsi Item</th>
                         <th class="py-2 text-center text-sm uppercase font-bold text-gray-900">Qty</th>
                         <th class="py-2 text-right text-sm uppercase font-bold text-gray-900">Harga Satuan</th>
                         <th class="py-2 text-right text-sm uppercase font-bold text-gray-900">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="text-sm text-gray-800">
                     @foreach($return->items as $item)
                        <tr class="border-b border-gray-100">
                            <td class="py-2">
                                <div class="font-bold">{{ $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus') }}</div>
                                @if(is_array($item->modifiers) && count($item->modifiers) > 0)
                                    <div class="text-xs text-gray-500 italic">
                                        + {{ collect($item->modifiers)->pluck('name')->implode(', ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="py-2 text-center">{{ $item->quantity }}</td>
                            <td class="py-2 text-right">{{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="py-2 text-right font-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                     @endforeach
                </tbody>
                <tfoot>
                     <tr>
                         <td colspan="3" class="py-4 text-right font-bold uppercase text-gray-600">Total Refund</td>
                         <td class="py-4 text-right font-bold text-xl text-gray-900">Rp {{ number_format($return->total_refund, 0, ',', '.') }}</td>
                     </tr>
                </tfoot>
            </table>
            
            <div class="border rounded-lg p-4 bg-gray-50 mb-8">
                 <p class="text-xs font-bold uppercase text-gray-500 mb-1">Alasan Retur:</p>
                 <p class="text-sm italic text-gray-700">"{{ $return->reason }}"</p>
            </div>

            <div class="flex justify-between mt-12 text-center">
                <div class="w-1/3">
                    <p class="text-sm font-bold text-gray-600 mb-16">Diterima Oleh</p>
                    <div class="border-t border-gray-400 mx-8"></div>
                    <p class="text-xs text-gray-400 mt-2">(Pelanggan)</p>
                </div>
                <div class="w-1/3">
                    <p class="text-sm font-bold text-gray-600 mb-16">Disetujui Oleh</p>
                    <div class="border-t border-gray-400 mx-8"></div>
                    <p class="text-xs text-gray-400 mt-2">(Manajer / Kasir)</p>
                </div>
            </div>
        </div>
    @endif
</body>
</html>
