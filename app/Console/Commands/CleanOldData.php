<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Transaction;
use App\Models\Shift;
use App\Models\ProductReturn;
use App\Models\StockLog;

class CleanOldData extends Command
{
    protected $signature = 'data:clean';
    protected $description = 'Membersihkan data transaksi, shift, retur, dan riwayat stok yang berumur lebih dari 3 bulan di DB Utama';

    public function handle()
    {
        $limitDate = now()->subMonths(3);

        $this->info("Memulai pembersihan data lama sebelum tanggal: " . $limitDate->toDateString());

        // Hapus Retur lama
        $deletedReturns = ProductReturn::where('created_at', '<', $limitDate)->forceDelete();
        $this->info("- Hapus data retur: {$deletedReturns} baris");

        // Hapus Transaksi lama (Detail transaksi otomatis terhapus karena cascade delete)
        $deletedTransactions = Transaction::where('created_at', '<', $limitDate)->forceDelete();
        $this->info("- Hapus data transaksi: {$deletedTransactions} baris");

        // Hapus Shift lama
        $deletedShifts = Shift::where('created_at', '<', $limitDate)->forceDelete();
        $this->info("- Hapus data shift: {$deletedShifts} baris");

        // Hapus Riwayat Stok lama
        $deletedStockLogs = StockLog::where('created_at', '<', $limitDate)->forceDelete();
        $this->info("- Hapus riwayat log stok: {$deletedStockLogs} baris");

        $this->info('Pembersihan database utama selesai!');
    }
}

