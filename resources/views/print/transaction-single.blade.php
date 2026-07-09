@php
    $settings = \App\Models\Setting::getGroup('general');
    $receiptSettings = \App\Models\Setting::getGroup('receipt');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Transaksi #{{ $transaction->invoice_number }}</title>
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
        .a4-font { font-family: sans-serif; }
    </style>
</head>
<body class="bg-white {{ ($format == '58mm' || $format == '76mm') ? 'p-1' : 'p-8 a4-font' }}" onload="window.print()">
    @if($format == '58mm' || $format == '76mm')
        <!-- Struk Layout (Adapted from partials.receipt) -->
        <div class="font-mono text-xs" style="max-width: {{ $format }};">
            <!-- Header -->
            <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
                <h1 class="text-base font-bold uppercase">{{ $settings['store_name'] ?? 'Bakso Malang' }}</h1>
                <p class="text-[10px] text-gray-500">{{ $settings['store_address'] ?? '' }}</p>
                @if(!empty($settings['store_phone']))
                <p class="text-[10px] text-gray-500">Telp: {{ $settings['store_phone'] }}</p>
                @endif
            </div>

            <!-- Transaction Info -->
            <div class="border-b border-dashed border-gray-300 pb-3 mb-3 text-[11px]">
                <div class="flex justify-between">
                    <span>No:</span><span class="font-bold">{{ $transaction->invoice_number }}</span>
                </div>
                @if($transaction->service_area_id)
                <div class="flex justify-between">
                    <span>Area:</span><span class="font-bold text-lg">{{ $transaction->serviceArea->name ?? '-' }}</span>
                </div>
                @endif
                <div class="flex justify-between">
                    <span>Tanggal:</span><span>{{ $transaction->created_at->format('d/m/y H:i') }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Kasir:</span><span>{{ $transaction->user->name ?? '-' }}</span>
                </div>
                @if($transaction->customer_name)
                    <div class="flex justify-between">
                        <span>Customer:</span><span>{{ $transaction->customer_name }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span>Tipe:</span><span>{{ $transaction->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}</span>
                </div>
            </div>

            <!-- Items -->
            <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
                @foreach($transaction->details as $detail)
                    <div class="mb-2">
                        <div class="font-bold">{{ $detail->product_name }}</div>
                        <div class="flex justify-between text-gray-600 pl-2 text-[10px]">
                            <span>{{ $detail->quantity }} x {{ number_format($detail->unit_price + $detail->modifier_total, 0, ',', '.') }}</span>
                            <span>{{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                        </div>
                        @if($detail->modifiers->count())
                            <div class="text-[9px] text-gray-400 pl-2 italic">
                                + {{ $detail->modifiers->pluck('name')->join(', ') }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <!-- Totals -->
            <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
                <div class="flex justify-between">
                    <span>Subtotal:</span><span>{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($transaction->tax_amount > 0)
                    <div class="flex justify-between">
                        <span>Pajak:</span><span>{{ number_format($transaction->tax_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-bold text-sm mt-1">
                    <span>TOTAL:</span><span>Rp {{ number_format($transaction->total, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Payment -->
            <div class="pb-3 text-[11px]">
                 <div class="flex justify-between">
                    <span>Bayar ({{ $transaction->paymentSource?->name ?? 'Cash' }}):</span>
                    <span>{{ number_format($transaction->paid_amount, 0, ',', '.') }}</span>
                </div>
                @if($transaction->change_amount > 0)
                    <div class="flex justify-between font-bold">
                        <span>Kembali:</span><span>{{ number_format($transaction->change_amount, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>

            @if($transaction->notes)
            <div class="border-b border-dashed border-gray-300 pb-3 mb-3">
                <span class="font-bold">Catatan:</span>
                <p class="text-gray-600 italic whitespace-pre-wrap">{{ $transaction->notes }}</p>
            </div>
            @endif

             <div class="text-center text-[10px] text-gray-400 mt-4">
                 {{ $receiptSettings['header_text'] ?? 'Terima Kasih!' }}
                 @if(!empty($receiptSettings['footer_text']))
                 <p class="mt-1">{{ $receiptSettings['footer_text'] }}</p>
                 @endif
             </div>
        </div>
    @else
        <!-- A4 Invoice Layout -->
        <div class="max-w-4xl mx-auto">
            <div class="flex justify-between items-start mb-8 border-b pb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">INVOICE</h1>
                    <p class="text-gray-500 mt-1">#{{ $transaction->invoice_number }}</p>
                </div>
                <div class="text-right">
                    <h2 class="font-bold text-xl">{{ $settings['store_name'] ?? 'Bakso Malang' }}</h2>
                    <p class="text-gray-500 text-sm">{{ $settings['store_address'] ?? '' }}</p>
                    @if(!empty($settings['store_phone']))
                    <p class="text-gray-500 text-sm">Telp: {{ $settings['store_phone'] }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Info Transaksi</h3>
                    <table class="text-sm">
                        <tr><td class="text-gray-500 py-1 pr-4">Tanggal:</td><td class="font-medium">{{ $transaction->created_at->format('d F Y, H:i') }}</td></tr>
                        <tr><td class="text-gray-500 py-1 pr-4">Kasir:</td><td class="font-medium">{{ $transaction->user->name ?? '-' }}</td></tr>
                        <tr><td class="text-gray-500 py-1 pr-4">Tipe:</td><td class="font-medium">{{ $transaction->order_type === 'dine_in' ? 'Dine In' : 'Take Away' }}</td></tr>
                         <tr><td class="text-gray-500 py-1 pr-4">Antrian:</td><td class="font-medium text-lg font-bold">{{ $transaction->queue_display }}</td></tr>
                    </table>
                </div>
                @if($transaction->customer_name)
                <div>
                    <h3 class="font-bold text-gray-700 mb-2">Customer</h3>
                    <p class="text-sm font-medium">{{ $transaction->customer_name }}</p>
                </div>
                @endif
            </div>

            <table class="w-full mb-8">
                <thead class="bg-gray-50 border-y border-gray-200">
                    <tr>
                        <th class="py-3 px-4 text-left font-semibold text-gray-600">Item</th>
                        <th class="py-3 px-4 text-center font-semibold text-gray-600">Qty</th>
                        <th class="py-3 px-4 text-right font-semibold text-gray-600">Harga</th>
                        <th class="py-3 px-4 text-right font-semibold text-gray-600">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transaction->details as $detail)
                        <tr>
                            <td class="py-3 px-4">
                                <p class="font-medium text-gray-800">{{ $detail->product_name }}</p>
                                @if($detail->modifiers->count())
                                    <p class="text-sm text-gray-500">+ {{ $detail->modifiers->pluck('name')->join(', ') }}</p>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-center">{{ $detail->quantity }}</td>
                            <td class="py-3 px-4 text-right">Rp {{ number_format($detail->unit_price + $detail->modifier_total, 0, ',', '.') }}</td>
                            <td class="py-3 px-4 text-right font-medium">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="border-t border-gray-200">
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right text-gray-600">Subtotal</td>
                        <td class="py-3 px-4 text-right font-medium">Rp {{ number_format($transaction->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($transaction->tax_amount > 0)
                    <tr>
                        <td colspan="3" class="py-3 px-4 text-right text-gray-600">Pajak</td>
                        <td class="py-3 px-4 text-right font-medium">Rp {{ number_format($transaction->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                    @endif
                     <tr class="text-lg">
                        <td colspan="3" class="py-3 px-4 text-right font-bold text-gray-800">TOTAL</td>
                        <td class="py-3 px-4 text-right font-bold text-primary-600">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>

            @if($transaction->notes)
            <div class="border rounded-lg p-4 bg-gray-50 mb-8">
                 <p class="text-xs font-bold uppercase text-gray-500 mb-1">Catatan:</p>
                 <p class="text-sm italic text-gray-700 whitespace-pre-wrap">{{ $transaction->notes }}</p>
            </div>
            @endif

            <div class="border-t pt-6">
                 <div class="flex justify-between items-center bg-gray-50 p-4 rounded-lg">
                     <div>
                         <span class="text-sm text-gray-500">Metode Pembayaran:</span>
                         <span class="font-bold ml-2">{{ $transaction->paymentSource?->name ?? 'Cash' }}</span>
                     </div>
                     <div class="text-sm text-gray-500">
                         Dicetak pada {{ now()->translatedFormat('d F Y H:i') }}
                     </div>
                 </div>
            </div>
        </div>
    @endif
</body>
</html>
