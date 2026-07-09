@php
    $settings = \App\Models\Setting::getGroup('general');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Shift Detail A4</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px double #000;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 16pt;
            margin: 0;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header p {
            margin: 2px 0;
            font-size: 10pt;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .info-grid {
            display: flex; /* Simpler than grid for print compatibility */
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .info-col {
            width: 48%;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            width: 140px;
            font-weight: bold;
        }
        .info-sep {
            width: 10px;
        }
        .info-val {
            flex: 1;
            text-align: right;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9pt; /* Slightly smaller for better fit */
        }
        .table th, .table td {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 4px; /* Tighter padding */
            vertical-align: top;
        }
        .table th {
            text-transform: uppercase;
            text-align: center;
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .col-money {
            white-space: nowrap;
            text-align: right;
            width: 10%; /* Ensure space */
        }
        .col-small {
            width: 40px;
            text-align: center;
        }
        .col-med {
             width: 80px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        .box-notes {
            border: 1px solid #000;
            padding: 10px;
            height: 60px;
            margin-bottom: 20px;
            font-size: 10pt;
        }

        .signatures {
            margin-top: 50px;
            display: flex;
            justify-content: flex-end;
            text-align: center;
        }
        .sig-box {
            width: 200px;
        }
        .sig-line {
            margin-top: 60px;
            border-bottom: 1px solid #000;
            font-weight: bold;
        }

        @media print {
            @page { margin: 1cm; size: {{ $format }}; }
        }
    </style>
</head>
<body onload="window.print()">
    <!-- Header -->
    <div class="header">
        <h1>{{ $settings['store_name'] ?? 'Bakso Malang Kobum' }}</h1>
        @if(!empty($settings['store_address']))
        <p>{{ $settings['store_address'] }}</p>
        @endif
        @if(!empty($settings['store_phone']))
        <p>Telp: {{ $settings['store_phone'] }}</p>
        @endif
    </div>

    <div class="title">LAPORAN REKAP PERGANTIAN SHIFT</div>

    @php
        $completed = $shift->transactions->where('status', 'completed');
        $salesTotal = $completed->sum('total');
    @endphp

    <div class="info-grid">
        <div class="info-col">
            <div class="info-row">
                <span class="info-label">Nama User</span><span class="info-sep">:</span>
                <span style="text-align: left;">{{ $shift->user->name }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Jam Buka</span><span class="info-sep">:</span>
                <span style="text-align: left;">{{ $shift->started_at->format('d M Y H:i:s') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Jam Tutup</span><span class="info-sep">:</span>
                <span style="text-align: left;">{{ $shift->ended_at?->format('d M Y H:i:s') ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Diserahkan Kepada</span><span class="info-sep">:</span>
                <span style="text-align: left;">-</span>
            </div>
        </div>
        <div class="info-col">
            <!-- Global Stats (For Owner) -->
            <div style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px; border-radius: 6px; background-color: #fcfcfc;">
                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 8px;">RINGKASAN GLOBAL (OWNER)</div>
                <div class="info-row" style="font-weight: bold; font-size: 12pt;">
                    <span class="info-label">Total Omset</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($salesTotal, 0, ',', '.') }}</span>
                </div>
                <div class="info-row" style="font-size: 9pt; color: #555;">
                    <span class="info-label" style="padding-left: 10px;">- Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->cash_sales, 0, ',', '.') }}</span>
                </div>
                <div class="info-row" style="font-size: 9pt; color: #555;">
                    <span class="info-label" style="padding-left: 10px;">- Non-Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->non_cash_sales, 0, ',', '.') }}</span>
                </div>
                <div class="info-row" style="margin-top: 5px; border-top: 1px dashed #ddd; paddingTop: 5px;">
                    <span class="info-label">Modal Awal</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->opening_cash, 0, ',', '.') }}</span>
                </div>
                 <div class="info-row" style="margin-top: 5px; border-top: 2px solid #ddd; paddingTop: 5px; font-weight: bold;">
                    <span class="info-label">Total</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->opening_cash + $salesTotal, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Cash Reconciliation Block -->
            <div style="padding: 5px 0;">
                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 5px;">💵 REKONSILIASI TUNAI (LACI)</div>
                <div class="info-row">
                    <span class="info-label">Modal Awal</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->opening_cash, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">(+) Penjualan Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->cash_sales, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">(-) Pengeluaran</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->expenses->sum('amount'), 0, ',', '.') }}</span>
                </div>
                <div class="info-row" style="background-color: #f9f9f9; font-weight: bold; border-top: 1px solid #ddd; margin-top: 5px; padding-top: 5px;">
                    <span class="info-label">Ekspektasi Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->expected_cash, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Fisik di Laci</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->actual_cash, 0, ',', '.') }}</span>
                </div>
                <div class="info-row" style="color: {{ $shift->cash_difference < 0 ? 'red' : 'black' }}; font-weight: bold;">
                    <span class="info-label">Selisih Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ ($shift->cash_difference >= 0 ? '+' : '') . number_format($shift->cash_difference, 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Non-Cash Reconciliation Block -->
            @if($shift->non_cash_sales > 0 || $shift->actual_non_cash !== null)
            <div style="padding: 5px 0; margin-top: 10px; border-top: 1px dashed #ccc;">
                <div style="font-weight: bold; text-decoration: underline; margin-bottom: 5px;">📱 REKONSILIASI NON-TUNAI (QRIS/TRANSFER/EDC)</div>
                <div class="info-row">
                    <span class="info-label">Sistem (Catatan)</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->expected_non_cash ?? $shift->non_cash_sales, 0, ',', '.') }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Terverifikasi Kasir</span><span class="info-sep">:</span>
                    <span class="info-val">{{ number_format($shift->actual_non_cash ?? 0, 0, ',', '.') }}</span>
                </div>
                @if($shift->non_cash_difference !== null)
                <div class="info-row" style="color: {{ $shift->non_cash_difference < 0 ? 'red' : 'black' }}; font-weight: bold;">
                    <span class="info-label">Selisih Non-Tunai</span><span class="info-sep">:</span>
                    <span class="info-val">{{ ($shift->non_cash_difference >= 0 ? '+' : '') . number_format($shift->non_cash_difference, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>
            @endif

        </div>
    </div>

    @if($shift->notes)
    <div style="font-weight: bold; margin-bottom: 5px;">Catatan:</div>
    <div class="box-notes">
        {{ $shift->notes }}
    </div>
    @endif

    <div style="text-align: center; font-weight: bold; margin-top: 20px; font-size: 11pt;">DAFTAR TRANSAKSI</div>
    
    <table class="table">
        <thead>
            <tr>
                <th class="col-small">No</th>
                <th class="col-med">Jam</th>
                <th>No. Faktur</th>
                <th>Pelanggan</th>
                <th class="col-med">Metode</th>
                <th class="col-money">Subtotal</th>
                <th class="col-money">Diskon</th>
                <th class="col-money">Tax</th>
                <th class="col-money">Grand Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($shift->transactions as $idx => $trx)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td class="text-center">{{ $trx->created_at->format('H:i') }}</td>
                <td class="text-center">{{ $trx->invoice_number }}</td>
                <td>{{ $trx->customer_name ?? '-' }}</td>
                <td class="text-center">{{ ucfirst($trx->payment_method) }}</td>
                <td class="col-money">{{ number_format($trx->subtotal, 0, ',', '.') }}</td>
                <td class="col-money">{{ number_format($trx->discount_amount, 0, ',', '.') }}</td>
                <td class="col-money">{{ number_format($trx->tax_amount, 0, ',', '.') }}</td>
                <td class="col-money font-bold">{{ number_format($trx->total, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="8" class="text-right">TOTAL</td>
                <td class="col-money">{{ number_format($salesTotal, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="signatures">
        <div class="sig-box">
            <div>{{ $settings['store_city'] ?? 'Kota' }}, {{ now()->isoFormat('D MMMM Y') }}</div>
            <div style="margin-top: 5px;">Penanggung Jawab</div>
            <div class="sig-line">{{ auth()->user()->name }}</div>
        </div>
    </div>
</body>
</html>
