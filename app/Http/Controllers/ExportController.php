<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\TransactionDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /**
     * Export transactions to CSV with chunking for large datasets
     */
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

            Transaction::query()
                ->with(['paymentSource', 'user'])
                ->whereBetween('created_at', [$start, $end])
                ->where('status', 'completed')
                ->when($search, fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                ->when($cashierId, fn($q) => $q->where('user_id', $cashierId))
                ->latest()
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

                    // Flush buffer to prevent timeout
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
        
        // Ensure directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $handle = fopen($tempPath, 'w');
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
        
        // Report Header
        fputcsv($handle, ['Bakso Malang'], ';');
        fputcsv($handle, ['LAPORAN DETAIL DATA PENJUALAN'], ';');
        fputcsv($handle, ['PERIODE ' . $start->format('d F Y') . ' - ' . $end->format('d F Y')], ';');
        fputcsv($handle, [''], ';'); // Empty line

        // Query: Join Master & Detail, Ordered by Transaction to allow grouping
        $query = \Illuminate\Support\Facades\DB::table('transaction_details')
            ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
            ->join('products', 'transaction_details.product_id', '=', 'products.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->leftJoin('payment_sources', 'transactions.payment_source_id', '=', 'payment_sources.id')
            ->whereBetween('transactions.created_at', [$start, $end])
            ->where('transactions.status', 'completed')
            ->when($search, fn($q) => $q->where('transactions.invoice_number', 'like', "%{$search}%"))
            ->when($cashierId, fn($q) => $q->where('transactions.user_id', $cashierId))
            ->orderBy('transactions.created_at', 'desc') // Grouping key
            ->orderBy('transactions.id', 'desc')         // Secondary grouping key
            ->select([
                'transactions.id as trans_id',
                'transactions.invoice_number',
                'transactions.created_at',
                'transactions.customer_name',
                'transactions.subtotal as trans_subtotal',
                'transactions.tax_amount',
                'transactions.total as trans_total',
                'users.name as cashier_name',
                'payment_sources.name as payment_name',
                'products.name as product_name',
                'transaction_details.unit_price',
                'transaction_details.quantity',
                'transaction_details.subtotal as item_subtotal',
                \Illuminate\Support\Facades\DB::raw('(SELECT GROUP_CONCAT(modifier_name SEPARATOR ", ") FROM transaction_detail_modifier WHERE transaction_detail_id = transaction_details.id) as modifiers_list')
            ]);

        // State Machine for Control Break
        $currentTransId = null;
        $currentTransData = null;

        foreach ($query->cursor() as $row) {
            // Check for new transaction group
            if ($currentTransId !== $row->trans_id) {
                
                // If not the very first iteration, print footer for the PREVIOUS transaction
                if ($currentTransId !== null) {
                    $this->writeTransactionFooter($handle, $currentTransData);
                }

                // Start NEW Transaction
                $currentTransId = $row->trans_id;
                $currentTransData = $row; // Save master data for footer usage
                
                // Print Transaction Header Block
                fputcsv($handle, [''], ';'); // Separator
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

                // Print Items Header
                fputcsv($handle, [
                    '', 'Menu', 'Harga', 'Qty', 'Subtotal'
                ], ';');
            }

            // Format Product Name with Modifiers
            $productName = $row->product_name;
            if (!empty($row->modifiers_list)) {
                $productName .= ' (' . $row->modifiers_list . ')';
            }

            // Print Item Row (Always)
            fputcsv($handle, [
                '',
                $productName,
                number_format($row->unit_price, 0, ',', '.'),
                $row->quantity,
                number_format($row->item_subtotal, 0, ',', '.')
            ], ';');
        }

        // Print footer for the LAST transaction
        if ($currentTransId !== null) {
            $this->writeTransactionFooter($handle, $currentTransData);
        }

        fclose($handle);

        return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
    }

    private function writeTransactionFooter($handle, $data)
    {
        // Hanya tampilkan Total sesuai request (simpel)
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

            // Use raw query for performance with grouping
            $query = TransactionDetail::query()
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->join('products', 'transaction_details.product_id', '=', 'products.id')
                ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
                ->where('transactions.status', 'completed')
                ->whereBetween('transactions.created_at', [$start, $end])
                ->selectRaw('
                    products.name as product_name,
                    categories.name as category_name,
                    SUM(transaction_details.quantity) as total_qty,
                    AVG(transaction_details.unit_price) as avg_price,
                    SUM(transaction_details.subtotal) as total_revenue
                ')
                ->groupBy('products.id', 'products.name', 'categories.name')
                ->orderByDesc('total_qty');

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

            $query = TransactionDetail::query()
                ->join('products', 'transaction_details.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->join('transactions', 'transaction_details.transaction_id', '=', 'transactions.id')
                ->where('transactions.status', 'completed')
                ->whereBetween('transactions.created_at', [$start, $end])
                ->groupBy('categories.id', 'categories.name')
                ->selectRaw('
                    categories.name as category_name,
                    SUM(transaction_details.quantity) as total_qty,
                    COUNT(DISTINCT transactions.id) as transaction_count,
                    SUM(transaction_details.subtotal) as total_sales
                ')
                ->orderByDesc('total_sales');

            foreach ($query->cursor() as $row) {
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

            $query = Transaction::query()
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('payment_method')
                ->selectRaw('
                    payment_method,
                    COUNT(*) as transaction_count,
                    SUM(total) as total_amount,
                    AVG(total) as average_amount
                ')
                ->orderByDesc('total_amount');

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

            // Optimasi: Gunakan Eager Loading
            $query = \App\Models\ProductReturn::with(['transaction', 'user', 'items.product'])
                ->whereBetween('created_at', [$start, $end])
                ->orderBy('created_at', 'desc');

            foreach ($query->cursor() as $return) {
                // Header Transaksi Retur
                fputcsv($handle, [
                    $return->return_number,
                    $return->transaction->invoice_number,
                    $return->user->name,
                    $return->created_at->format('d/m/Y H:i'),
                    $return->reason,
                    number_format($return->total_refund, 0, ',', '.')
                ], ';');

                // Detail Item
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

    public function printReturn(\App\Models\ProductReturn $return)
    {
        $return->load(['items.product', 'transaction', 'user', 'shift']);
        return view('admin.exports.print-return', compact('return'));
    }
}
