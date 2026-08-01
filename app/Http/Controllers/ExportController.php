<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Shift;
use App\Models\ProductReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export transactions to CSV (Summary) - Optimized Streaming
     */
    public function transactions(Request $request)
    {
        set_time_limit(0); 
        ini_set('memory_limit', '512M');

        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');
        $cashierId = $request->query('cashier');
        $filename = 'Transaksi_' . $start->format('d_M_Y') . '_sd_' . $end->format('d_M_Y') . '.csv';

        return response()->streamDownload(function () use ($start, $end, $search, $cashierId) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
            
            fputcsv($handle, [
                'Invoice', 'Tanggal', 'Waktu', 'Kasir', 'Pelanggan', 'Metode Bayar', 'Total', 'Status'
            ], ';');

            Transaction::getExportQuery($start, $end, $search ?: null, $cashierId)
                ->cursor()
                ->each(function ($t) use ($handle) {
                    fputcsv($handle, [
                        $t->invoice_number,
                        $t->created_at->format('d/m/Y'),
                        $t->created_at->format('H:i'),
                        $t->user?->name ?? '-',
                        $t->customer_name ?: '-',
                        $t->paymentSource?->name ?? '-',
                        $t->total,
                        'Selesai',
                    ], ';');

                    if (ob_get_level() > 0) ob_flush();
                    flush();
                });

            fclose($handle);
        }, $filename);
    }

    /**
     * Export transactions to CSV (Detail Item) - Hierarchical Format
     */
    public function transactionsDetail(Request $request)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $search = $request->query('search');
        $cashierId = $request->query('cashier');

        // Generate Temp File
        $filename = 'Laporan_Detail_' . $start->format('d_M_Y') . '_sd_' . $end->format('d_M_Y') . '.csv';
        $tempPath = storage_path('app/public/exports/' . uniqid() . '_' . $filename);
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $handle = fopen($tempPath, 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        fputcsv($handle, ['Bakso Malang'], ';');
        fputcsv($handle, ['LAPORAN DETAIL DATA PENJUALAN'], ';');
        fputcsv($handle, ['PERIODE ' . $start->format('d F Y') . ' - ' . $end->format('d F Y')], ';');
        fputcsv($handle, [''], ';');

        $query = TransactionDetail::getTransactionsDetailExportQuery($start, $end, $search ?: null, $cashierId);

        $currentTransId = null;
        $currentTransData = null;

        foreach ($query->cursor() as $row) {
            if ($currentTransId !== $row->trans_id) {
                if ($currentTransId !== null) {
                    $this->writeTransactionFooter($handle, $currentTransData);
                }

                $currentTransId = $row->trans_id;
                $currentTransData = $row;
                
                fputcsv($handle, [''], ';');
                fputcsv($handle, [
                    'No Faktur', 'Tanggal', 'Waktu', 'Kasir', 'Pelanggan', 'Metode Bayar'
                ], ';');
                fputcsv($handle, [
                    $row->invoice_number,
                    Carbon::parse($row->created_at)->format('d/m/Y'),
                    Carbon::parse($row->created_at)->format('H:i'),
                    $row->cashier_name ?? '-',
                    $row->customer_name ?: 'Umum',
                    $row->payment_name ?? 'Tunai'
                ], ';');

                fputcsv($handle, [
                    '', 'Menu', 'Harga', 'Qty', 'Subtotal'
                ], ';');
            }

            $productName = $row->product_name;
            if (!empty($row->modifiers_list)) {
                $productName .= ' (' . $row->modifiers_list . ')';
            }

            fputcsv($handle, [
                '',
                $productName,
                number_format($row->unit_price, 0, ',', '.'),
                $row->quantity,
                number_format($row->item_subtotal, 0, ',', '.')
            ], ';');
        }

        if ($currentTransId !== null) {
            $this->writeTransactionFooter($handle, $currentTransData);
        }

        fclose($handle);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function writeTransactionFooter($handle, $data)
    {
        fputcsv($handle, [
            '', '', '', 'TOTAL :', number_format($data->trans_total, 0, ',', '.')
        ], ';');
    }

    /**
     * Export sales by product to CSV
     */
    public function productSales(Request $request): StreamedResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $filename = 'penjualan_produk_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($handle, ['LAPORAN PENJUALAN PER PRODUK'], ';');
            fputcsv($handle, ['Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y')], ';');
            fputcsv($handle, [''], ';');

            fputcsv($handle, [
                'Produk',
                'Kategori',
                'Qty Terjual',
                'Harga Rata-rata',
                'Total Pendapatan'
            ], ';');

            $query = TransactionDetail::getProductSalesExportQuery($start, $end);

            foreach ($query->cursor() as $row) {
                fputcsv($handle, [
                    $row->product_name,
                    $row->category_name ?? 'Tanpa Kategori',
                    $row->total_qty,
                    number_format($row->avg_price, 0, ',', '.'),
                    number_format($row->total_revenue, 0, ',', '.')
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function salesByCategory(Request $request): StreamedResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $filename = 'penjualan_kategori_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN PENJUALAN PER KATEGORI'], ';');
            fputcsv($handle, ['Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y')], ';');
            fputcsv($handle, [''], ';');

            fputcsv($handle, [
                'Kategori',
                'Total Qty',
                'Total Transaksi',
                'Total Pendapatan'
            ], ';');

            $query = TransactionDetail::getCategorySalesReport($start, $end);

            foreach ($query as $row) {
                fputcsv($handle, [
                    $row->category_name,
                    $row->total_qty,
                    $row->transaction_count,
                    number_format($row->total_sales, 0, ',', '.')
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function salesByPaymentMethod(Request $request): StreamedResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $filename = 'metode_pembayaran_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN PENJUALAN PER METODE PEMBAYARAN'], ';');
            fputcsv($handle, ['Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y')], ';');
            fputcsv($handle, [''], ';');

            fputcsv($handle, [
                'Metode Pembayaran',
                'Jumlah Transaksi',
                'Total Pendapatan',
                'Rata-rata'
            ], ';');

            $query = Transaction::getSalesByPaymentMethodExportQuery($start, $end);

            foreach ($query->cursor() as $row) {
                fputcsv($handle, [
                    ucfirst($row->payment_method),
                    $row->transaction_count,
                    number_format($row->total_amount, 0, ',', '.'),
                    number_format($row->average_amount, 0, ',', '.')
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function productReturns(Request $request): StreamedResponse
    {
        $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $start = Carbon::parse($request->start)->startOfDay();
        $end = Carbon::parse($request->end)->endOfDay();
        $filename = 'laporan_retur_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($start, $end) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['LAPORAN RETUR PRODUK'], ';');
            fputcsv($handle, ['Periode: ' . $start->format('d M Y') . ' - ' . $end->format('d M Y')], ';');
            fputcsv($handle, [''], ';');

            fputcsv($handle, [
                'No. Retur / Item',
                'Invoice',
                'Kasir',
                'Waktu',
                'Alasan / Qty',
                'Total / Subtotal'
            ], ';');

            $query = ProductReturn::getExportQuery($start, $end);

            foreach ($query->cursor() as $return) {
                fputcsv($handle, [
                    $return->return_number,
                    $return->transaction->invoice_number,
                    $return->user->name,
                    $return->created_at->format('d/m/Y H:i'),
                    $return->reason,
                    number_format($return->total_refund, 0, ',', '.')
                ], ';');

                foreach ($return->items as $item) {
                    $productName = $item->product ? $item->product->name : ($item->product_name ?? 'Item Terhapus');
                    if (is_array($item->modifiers) && count($item->modifiers) > 0) {
                        $productName .= ' (' . collect($item->modifiers)->pluck('name')->implode(', ') . ')';
                    }
                    fputcsv($handle, [
                        '  > ' . $productName,
                        '',
                        '',
                        '',
                        $item->quantity . ' x @' . number_format($item->unit_price, 0, ',', '.'),
                        number_format($item->subtotal, 0, ',', '.')
                    ], ';');
                }
                
                fputcsv($handle, [], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function printReturn(ProductReturn $return)
    {
        $return->loadForPrint();
        return view('admin.exports.print-return', compact('return'));
    }

    public function shifts(Request $request)
    {
        set_time_limit(0); 
        ini_set('memory_limit', '512M');

        $start = Carbon::parse($request->query('start'))->startOfDay();
        $end = Carbon::parse($request->query('end'))->endOfDay();
        $cashierId = $request->query('cashier');
        $filename = 'Laporan_Shift_' . $start->format('d_M_Y') . '_sd_' . $end->format('d_M_Y') . '.csv';

        return response()->streamDownload(function () use ($start, $end, $cashierId) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
            
            fputcsv($handle, [
                'Kasir',
                'Tanggal',
                'Mulai',
                'Selesai',
                'Modal Awal',
                'Total Penjualan',
                'Penjualan Tunai',
                'Penjualan Non-Tunai',
                'Pengeluaran',
                'Ekspektasi Tunai (Sistem)',
                'Fisik di Laci (Kasir)',
                'Selisih Tunai',
                'Non-Tunai Sistem',
                'Non-Tunai Terverifikasi (Kasir)',
                'Selisih Non-Tunai',
                'Status',
                'Catatan',
            ], ';');

            Shift::getExportQuery($start, $end, $cashierId)
                ->cursor()
                ->each(function ($shift) use ($handle) {
                    $completedTrx   = $shift->transactions->where('status', 'completed');
                    $totalSales     = $completedTrx->sum('total');
                    $cashSales      = $completedTrx->where('payment_method', 'cash')->sum('total');
                    $nonCashSales   = $completedTrx->where('payment_method', '!=', 'cash')->sum('total');
                    $expenses       = $shift->expenses->sum('amount');
                    
                    fputcsv($handle, [
                        $shift->user->name,
                        $shift->started_at->format('d/m/Y'),
                        $shift->started_at->format('H:i'),
                        $shift->ended_at ? $shift->ended_at->format('H:i') : '-',
                        $shift->opening_cash,
                        $totalSales,
                        $cashSales,
                        $nonCashSales,
                        $expenses,
                        $shift->expected_cash,
                        $shift->actual_cash,
                        $shift->cash_difference,
                        $shift->expected_non_cash ?? $nonCashSales,
                        $shift->actual_non_cash,
                        $shift->non_cash_difference,
                        $shift->status === 'closed' ? 'Ditutup' : 'Aktif',
                        $shift->notes,
                    ], ';');

                    if (ob_get_level() > 0) ob_flush();
                    flush();
                });

            fclose($handle);
        }, $filename);
    }
}
