<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Component;
use App\Models\ComponentStockLog;
use App\Models\Ingredient;
use App\Models\IngredientStockLog;
use App\Models\Pager;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\ProductReturn;
use App\Models\Production;
use App\Models\Purchase;
use App\Models\SelfOrder;
use App\Models\ServiceArea;
use App\Models\Shift;
use App\Models\Transaction;
use App\Services\ReportSyncService;
use Illuminate\Console\Command;

class SyncReportData extends Command
{
    protected $signature = 'report:sync-all
                            {--chunk=100 : Jumlah record per batch}
                            {--only= : Hanya sync tipe data tertentu (transactions,shifts,returns,purchases,productions)}';

    protected $description = 'Sinkronisasi penuh semua data';

    public function __construct(private readonly ReportSyncService $reportSyncService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');
        $only  = $this->option('only');

        $this->info('Memulai sinkronisasi data ke ');
        $this->newLine();

        $start = now();

        if (!$only || str_contains($only, 'master')) {
            $this->syncMasterData();
        }

        if (!$only || str_contains($only, 'shifts')) {
            $this->syncShifts($chunk);
        }

        if (!$only || str_contains($only, 'transactions')) {
            $this->syncTransactions($chunk);
        }

        if (!$only || str_contains($only, 'returns')) {
            $this->syncReturns($chunk);
        }

        if (!$only || str_contains($only, 'purchases')) {
            $this->syncPurchases($chunk);
        }

        if (!$only || str_contains($only, 'productions')) {
            $this->syncProductions($chunk);
        }

        if (!$only || str_contains($only, 'stock_logs')) {
            $this->syncStockLogs($chunk);
        }

        $elapsed = now()->diffInSeconds($start);
        $this->newLine();
        $this->info("✅ Sinkronisasi selesai dalam {$elapsed} detik.");

        return self::SUCCESS;
    }

    private function syncMasterData(): void
    {
        $this->line('📦 Sync Data Master...');

        // Users (withTrashed: user nonaktif tetap harus tersinkron dengan deleted_at terisi)
        $users = \App\Models\User::withTrashed()->get();
        $this->withProgressBar($users, fn($u) => $this->reportSyncService->syncUser($u));
        $this->line(" ✓ User: {$users->count()}");

        // Payment Sources
        $ps = PaymentSource::withTrashed()->get();
        $this->withProgressBar($ps, fn($p) => $this->reportSyncService->syncPaymentSource($p));
        $this->line(" ✓ Payment Sources: {$ps->count()}");

        // Service Areas
        $sa = ServiceArea::withTrashed()->get();
        $this->withProgressBar($sa, fn($s) => $this->reportSyncService->syncServiceArea($s));
        $this->line(" ✓ Service Areas: {$sa->count()}");

        // Pagers
        $pagers = Pager::withTrashed()->get();
        $this->withProgressBar($pagers, fn($p) => $this->reportSyncService->syncPager($p));
        $this->line(" ✓ Pagers: {$pagers->count()}");

        // Categories
        $cats = Category::withTrashed()->get();
        $this->withProgressBar($cats, fn($c) => $this->reportSyncService->syncCategory($c));
        $this->line(" ✓ Categories: {$cats->count()}");

        // Products
        $prods = \App\Models\Product::withTrashed()->get();
        $this->withProgressBar($prods, fn($p) => $this->reportSyncService->ensureProductSyncedPublic($p));
        $this->line(" ✓ Products: {$prods->count()}");

        // Modifier Groups & Modifiers
        $groups = \App\Models\ModifierGroup::withTrashed()->get();
        $this->withProgressBar($groups, fn($g) => $this->reportSyncService->syncModifierGroup($g));
        $this->line(" ✓ Modifier Groups: {$groups->count()}");

        $mods = \App\Models\Modifier::withTrashed()->get();
        $this->withProgressBar($mods, fn($m) => $this->reportSyncService->syncModifierPublic($m));
        $this->line(" ✓ Modifiers: {$mods->count()}");

        // Ingredients & Components
        $ings = Ingredient::withTrashed()->get();
        $this->withProgressBar($ings, fn($i) => $this->reportSyncService->syncIngredient($i));
        $this->line(" ✓ Ingredients: {$ings->count()}");

        $comps = Component::withTrashed()->get();
        $this->withProgressBar($comps, fn($c) => $this->reportSyncService->syncComponent($c));
        $this->line(" ✓ Components: {$comps->count()}");
    }

    private function syncShifts(int $chunk): void
    {
        $this->line('⏱ Sync Shift...');
        $total = 0;

        Shift::with('expenses')->chunkById($chunk, function ($shifts) use (&$total) {
            foreach ($shifts as $shift) {
                $this->reportSyncService->syncShift($shift);
                $total++;
            }
        });

        $this->line(" ✓ Shifts: {$total}");
    }

    private function syncTransactions(int $chunk): void
    {
        $this->line('💳 Sync Transaksi...');
        $total = 0;

        // Sync PaymentTransactions terlebih dahulu (FK dari transactions)
        $ptTotal = 0;
        PaymentTransaction::chunkById($chunk, function ($paymentTxs) use (&$ptTotal) {
            foreach ($paymentTxs as $pt) {
                $this->reportSyncService->syncPaymentTransaction($pt);
                $ptTotal++;
            }
        });
        $this->line(" ✓ Payment Transactions: {$ptTotal}");

        // Sync Self Orders
        $soTotal = 0;
        SelfOrder::chunkById($chunk, function ($selfOrders) use (&$soTotal) {
            foreach ($selfOrders as $so) {
                $this->reportSyncService->syncSelfOrder($so);
                $soTotal++;
            }
        });
        $this->line(" ✓ Self Orders: {$soTotal}");

        // Sync Transaksi POS
        Transaction::with('details.modifiers')->chunkById($chunk, function ($transactions) use (&$total) {
            foreach ($transactions as $transaction) {
                $this->reportSyncService->syncTransaction($transaction);
                $total++;
            }
        });
        $this->line(" ✓ Transactions: {$total}");
    }

    private function syncReturns(int $chunk): void
    {
        $this->line('↩ Sync Retur...');
        $total = 0;

        ProductReturn::with('items')->chunkById($chunk, function ($returns) use (&$total) {
            foreach ($returns as $return) {
                $this->reportSyncService->syncReturn($return);
                $total++;
            }
        });

        $this->line(" ✓ Returns: {$total}");
    }

    private function syncPurchases(int $chunk): void
    {
        $this->line('🛒 Sync Pembelian...');
        $total = 0;

        Purchase::with('items')->chunkById($chunk, function ($purchases) use (&$total) {
            foreach ($purchases as $purchase) {
                $this->reportSyncService->syncPurchase($purchase);
                $total++;
            }
        });

        $this->line(" ✓ Purchases: {$total}");
    }

    private function syncProductions(int $chunk): void
    {
        $this->line('🏭 Sync Produksi...');
        $total = 0;

        Production::with(['inputs', 'outputs'])->chunkById($chunk, function ($productions) use (&$total) {
            foreach ($productions as $production) {
                $this->reportSyncService->syncProduction($production);
                $total++;
            }
        });

        $this->line(" ✓ Productions: {$total}");
    }

    private function syncStockLogs(int $chunk): void
    {
        $this->line('📊 Sync Log Stok...');
        $ingTotal = 0;
        $compTotal = 0;

        IngredientStockLog::chunkById($chunk, function ($logs) use (&$ingTotal) {
            foreach ($logs as $log) {
                $this->reportSyncService->syncIngredientStockLog($log);
                $ingTotal++;
            }
        });
        $this->line(" ✓ Ingredient Stock Logs: {$ingTotal}");

        ComponentStockLog::chunkById($chunk, function ($logs) use (&$compTotal) {
            foreach ($logs as $log) {
                $this->reportSyncService->syncComponentStockLog($log);
                $compTotal++;
            }
        });
        $this->line(" ✓ Component Stock Logs: {$compTotal}");
    }
}
