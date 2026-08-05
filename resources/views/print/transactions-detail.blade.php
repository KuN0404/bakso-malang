@php
    $settings = \App\Models\Setting::getGroup('general');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Detail Transaksi</title>
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
        /* Common */
        body { font-family: 'Courier New', Courier, monospace; font-size: 12px; }
        
        /* A4 Specific */
        .a4-font { font-family: sans-serif; }
        .transaction-card { border: 1px solid #ddd; margin-bottom: 15px; page-break-inside: avoid; }
        .transaction-header { background-color: #f5f5f5; padding: 8px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; }
        .transaction-body { padding: 8px; }
        .a4-table { width: 100%; border-collapse: collapse; }
        .a4-table th, .a4-table td { padding: 4px; text-align: left; }
        .item-row td { border-bottom: 1px dashed #eee; }
        .modifier { font-size: 10px; color: #666; margin-left: 10px; }
        .grand-total { margin-top: 20px; text-align: right; font-size: 14px; border-top: 2px solid #333; padding-top: 10px; }
    </style>
</head>
<body class="bg-white {{ ($format == '58mm' || $format == '76mm') ? 'p-1' : 'p-8 a4-font' }}" onload="window.print()">
    @if($format == '58mm' || $format == '76mm')
        <div class="text-xs font-mono max-w-[{{ $format }}]">
             <!-- Header -->
             <div class="text-center border-b border-dashed border-gray-400 pb-3 mb-3">
                 <h1 class="text-sm font-bold uppercase">Detail Transaksi</h1>
                 <p class="text-[10px] mt-1">{{ $start->format('d/m/y') }} - {{ $end->format('d/m/y') }}</p>
             </div>

             @foreach($transactions as $transaction)
                 <div class="border-b border-dashed border-gray-300 pb-2 mb-2">
                     <!-- Transaction Header -->
                     <div class="flex justify-between font-bold">
                         <span>{{ $transaction->invoice_number }}</span>
                         <span>{{ $transaction->created_at->format('d/m H:i') }}</span>
                     </div>
                     <div class="text-[10px] text-gray-500 mb-1">
                         {{ $transaction->user?->name ?? '-' }} | {{ $transaction->paymentSource?->name ?? 'Cash' }}
                     </div>

                     <!-- Items -->
                     <div class="pl-2 border-l border-dashed border-gray-300 ml-1">
                         @foreach($transaction->details as $detail)
                             <div class="mb-1">
                                 <div class="font-semibold">{{ $detail->product->name }}</div>
                                 @if($detail->modifiers->count())
                                     <div class="text-[9px] text-gray-400 italic">
                                         + @foreach($detail->modifiers as $mod)
                                             {{ $mod->pivot->modifier_name ?? $mod->name }}{{ ($mod->pivot->quantity ?? 1) > 1 ? ' ×' . $mod->pivot->quantity : '' }}{{ !$loop->last ? ', ' : '' }}
                                         @endforeach
                                     </div>
                                 @endif
                                 <div class="flex justify-between text-[10px] text-gray-600">
                                     <span>{{ $detail->quantity }} x {{ number_format($detail->price, 0, ',', '.') }}</span>
                                     <span>{{ number_format($detail->subtotal, 0, ',', '.') }}</span>
                                 </div>
                             </div>
                         @endforeach
                     </div>
                     
                     <!-- Total -->
                     <div class="text-right font-bold mt-1 text-[11px]">
                         Total: Rp {{ number_format($transaction->total, 0, ',', '.') }}
                     </div>
                 </div>
             @endforeach

             <!-- Grand Total -->
             <div class="border-t-2 border-dashed border-gray-500 pt-2 mt-2">
                 <div class="flex justify-between font-bold text-sm">
                     <span>TOTAL</span>
                     <span>Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</span>
                 </div>
                 <div class="text-center text-[10px] text-gray-400 mt-2">
                     {{ $summary['total_transactions'] }} Transaksi
                 </div>
                 <div class="text-center text-[9px] text-gray-300 mt-4">
                     Dicetak: {{ now()->translatedFormat('d F Y H:i') }}
                 </div>
             </div>
        </div>
    @else
        <!-- A4 Layout (Existing) -->
        <div class="text-center mb-6">
            <h1 class="text-xl font-bold">Laporan Detail Transaksi</h1>
            <p class="text-gray-600">{{ $settings['store_name'] ?? 'Bakso Malang' }}</p>
            <p class="text-sm text-gray-500">Periode: {{ $start->format('d M Y') }} - {{ $end->format('d M Y') }}</p>
        </div>

        @foreach($transactions as $transaction)
        <div class="transaction-card">
            <div class="transaction-header">
                <span><strong>{{ $transaction->invoice_number }}</strong></span>
                <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="transaction-body">
                <div style="margin-bottom: 5px; font-size: 11px; color: #555;">
                    Kasir: {{ $transaction->user?->name ?? '-' }} | Pembayaran: {{ $transaction->paymentSource?->name ?? 'Cash' }}
                </div>
                <table class="a4-table">
                    <tbody>
                        @foreach($transaction->details as $detail)
                        <tr class="item-row">
                            <td width="50%">
                                {{ $detail->product->name }}
                                  @if($detail->modifiers->count() > 0)
                                      <div class="modifier">
                                          + @foreach($detail->modifiers as $mod)
                                              {{ $mod->pivot->modifier_name ?? $mod->name }}{{ ($mod->pivot->quantity ?? 1) > 1 ? ' ×' . $mod->pivot->quantity : '' }}{{ !$loop->last ? ', ' : '' }}
                                          @endforeach
                                      </div>
                                  @endif
                            </td>
                            <td width="15%" class="text-center">{{ $detail->quantity }}x</td>
                            <td width="20%" class="text-right">{{ number_format($detail->price, 0, ',', '.') }}</td>
                            <td width="15%" class="text-right">{{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="text-right" style="margin-top: 5px; font-weight: bold;">
                    Total: Rp {{ number_format($transaction->total, 0, ',', '.') }}
                </div>
            </div>
        </div>
        @endforeach

        <div class="grand-total">
            <strong>Total Keseluruhan: Rp {{ number_format($summary['total_revenue'], 0, ',', '.') }}</strong><br>
            <span style="font-size: 12px; font-weight: normal;">Total Transaksi: {{ number_format($summary['total_transactions']) }}</span>
        </div>
    @endif
</body>
</html>
