<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Component;
use App\Models\ComponentStockLog;
use App\Models\Modifier;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\TransactionDetail;
use App\Models\TransactionDetailComponent;
use Illuminate\Support\Facades\Log;

/**
 * Service terpusat untuk semua operasi stok komponen.
 *
 * Semua method yang memodifikasi stok HARUS dipanggil dalam DB::transaction().
 * Gagal akuisisi lock atau stok tidak cukup → InsufficientStockException → rollback.
 */
class ComponentStockService
{
    // -----------------------------------------------------------------
    // Checkout Operations
    // -----------------------------------------------------------------

    /**
     * Kurangi stok komponen berdasarkan BOM produk.
     *
     * Dipanggil saat checkout untuk setiap item cart yang produknya memiliki BOM.
     * Menggunakan lockForUpdate() untuk mencegah race condition pada concurrent orders.
     *
     * @throws InsufficientStockException
     */
    public function deductForBom(
        int $productId,
        int $productQty,
        int $transactionId,
        int $userId,
        array $subMap = [],
        ?TransactionDetail $detail = null
    ): void {
        $product = Product::with('bom.component')->find($productId);

        if (!$product) {
            throw new \RuntimeException("Produk ID [{$productId}] tidak ditemukan saat pengurangan stok BOM.");
        }

        // Agregasi kebutuhan per komponen DULU, baru dipotong. Kalau dipotong per
        // baris BOM, satu komponen yang muncul di dua baris (normal + pengganti)
        // akan dicek dua kali secara terpisah dan bisa lolos walau totalnya kurang.
        $perUnit = app(ComponentDemandResolver::class)->demandPerUnit($product, $subMap);

        // Urutan lock deterministik (component_id menaik) untuk mencegah deadlock
        // ABBA saat dua kasir checkout bersamaan dengan komponen yang beririsan.
        $componentIds = array_keys($perUnit);
        sort($componentIds);

        foreach ($componentIds as $componentId) {
            $needed    = $perUnit[$componentId] * $productQty;
            $component = Component::lockForUpdate()->find($componentId);

            if (!$component) {
                throw new \RuntimeException(
                    "Komponen ID [{$componentId}] tidak ditemukan dalam BOM produk ID [{$productId}]."
                );
            }

            $newStock = $component->stock - $needed;

            if ($newStock < 0) {
                throw new InsufficientStockException(
                    "Stok komponen '{$component->name}' tidak cukup. "
                    . "Dibutuhkan: {$needed} {$component->unit?->symbol}, "
                    . "Tersisa: {$component->stock} {$component->unit?->symbol}."
                );
            }

            $component->update(['stock' => $newStock]);

            ComponentStockLog::record(
                componentId:   $component->id,
                userId:        $userId,
                type:          'bom_deduct',
                amount:        -$needed,
                finalStock:    $newStock,
                note:          "BOM Deduct: Transaksi #{$transactionId}",
                referenceId:   $transactionId,
                referenceType: 'transaction',
            );

            // Log warning jika stok menipis setelah deduction
            if ($component->fresh()->isLowStock()) {
                Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit?->symbol} (min: {$component->minimum_stock})");
            }
        }

        $this->writeComponentSnapshot($detail, $product, $subMap, $productQty, $perUnit);
    }

    /**
     * Catat komposisi komponen yang benar-benar dipakai pada baris transaksi ini.
     *
     * Tanpa snapshot ini, retur menghitung ulang dari BOM yang hidup — salah untuk
     * penjualan bersubstitusi (mengembalikan komponen yang tidak pernah dipotong)
     * dan salah untuk BOM yang diubah admin setelah penjualan.
     *
     * @param  array  $perUnit    Kebutuhan teragregasi per komponen, per 1 unit produk.
     * @param  array|null  $actualTotals  Jika diisi (jalur best-effort), dipakai sebagai
     *                                    quantity_total menggantikan perUnit × qty.
     */
    public function writeComponentSnapshot(
        ?TransactionDetail $detail,
        Product $product,
        array $subMap,
        int $productQty,
        array $perUnit,
        ?array $actualTotals = null
    ): void {
        if (!$detail) {
            return; // pemanggil lama (4 argumen) tidak menyediakan detail — lewati
        }

        // Peta komponen pengganti → baris BOM asal yang digantikan, untuk audit.
        $replacedBy = [];
        foreach ($product->bom as $line) {
            if (isset($subMap[$line->id])) {
                $subComponentId = (int) ($subMap[$line->id]['component_id'] ?? 0);
                if ($subComponentId > 0) {
                    $replacedBy[$subComponentId] = [
                        'product_bom_id'        => $line->id,
                        'replaced_component_id' => (int) $line->component_id,
                        'replaced_quantity'     => (float) $line->quantity,
                        'substitution_rule_id'  => $subMap[$line->id]['rule_id'] ?? null,
                    ];
                }
            }
        }

        $originalLineByComponent = $product->bom->keyBy('component_id');

        foreach ($perUnit as $componentId => $qtyPerUnit) {
            $component = Component::find($componentId);
            $meta      = $replacedBy[$componentId] ?? null;

            $detail->components()->create([
                'component_id'          => $componentId,
                'component_name'        => $component?->name ?? "Komponen #{$componentId}",
                'quantity_per_unit'     => round($qtyPerUnit, 3),
                // quantity_total = yang BENAR-BENAR dipotong. Pada jalur best-effort
                // bisa lebih kecil dari niatnya (oversold) — retur wajib memakai ini.
                'quantity_total'        => round($actualTotals[$componentId] ?? ($qtyPerUnit * $productQty), 3),
                'source'                => $meta ? 'substitute' : 'bom',
                'replaced_component_id' => $meta['replaced_component_id'] ?? null,
                'replaced_quantity'     => isset($meta) ? $meta['replaced_quantity'] * $productQty : null,
                'product_bom_id'        => $meta['product_bom_id'] ?? $originalLineByComponent->get($componentId)?->id,
                'substitution_rule_id'  => $meta['substitution_rule_id'] ?? null,
            ]);
        }
    }

    /**
     * Tulis snapshot komposisi komponen untuk transaksi hasil Self Order.
     *
     * Self Order memotong stok lewat StockReservationService (reservasi → konversi),
     * bukan lewat deductForBom(), sehingga snapshot tidak bisa ditulis dari sana:
     * baris stock_reservations sama sekali tidak menyimpan product_id/cart line, jadi
     * di titik konversi tidak diketahui komponen itu milik TransactionDetail yang mana.
     * Karena itu snapshot ditulis di sini — dari sisi yang memegang $detail beserta
     * produk dan qty-nya.
     *
     * $actualDeducted adalah hasil convertForSelfOrder(): [component_id => total yang
     * benar-benar terpotong]. Pada jalur normal jumlahnya sama dengan yang diniatkan,
     * pada settlement terlambat yang oversold bisa lebih kecil — maka porsi tiap detail
     * diskalakan proporsional supaya retur tidak pernah mengembalikan lebih banyak
     * daripada yang pernah keluar.
     *
     * @param  \Illuminate\Support\Collection|array  $details  TransactionDetail yang baru dibuat
     * @param  array  $actualDeducted  [component_id => total benar-benar terpotong]
     */
    public function recordSelfOrderSnapshots(iterable $details, array $actualDeducted): void
    {
        $resolver = app(ComponentDemandResolver::class);

        // Tahap 1 — kumpulkan niat (intended) per detail dan totalnya per komponen.
        $planned       = [];
        $intendedTotal = [];

        foreach ($details as $detail) {
            $product = $detail->product;

            if (!$product || !$product->hasBom()) {
                continue;
            }

            // Self Order tidak mendukung substitusi → subMap selalu kosong.
            $perUnit = $resolver->demandPerUnit($product);

            if (empty($perUnit)) {
                continue;
            }

            $planned[] = ['detail' => $detail, 'product' => $product, 'perUnit' => $perUnit];

            foreach ($perUnit as $componentId => $qtyPerUnit) {
                $intendedTotal[$componentId] = ($intendedTotal[$componentId] ?? 0)
                    + ($qtyPerUnit * (int) $detail->quantity);
            }
        }

        // Tahap 2 — rasio realisasi per komponen. min(1, ...) menjaga agar komponen yang
        // juga dipakai modifier (deduksinya ikut masuk $actualDeducted) tidak membuat
        // rasio melebihi 1.
        $ratio = [];
        foreach ($intendedTotal as $componentId => $intended) {
            $ratio[$componentId] = $intended > 0
                ? min(1.0, (float) ($actualDeducted[$componentId] ?? 0) / $intended)
                : 1.0;
        }

        // Tahap 3 — tulis snapshot per detail dengan porsi yang sudah diskalakan.
        foreach ($planned as $row) {
            $qty          = (int) $row['detail']->quantity;
            $actualTotals = [];

            foreach ($row['perUnit'] as $componentId => $qtyPerUnit) {
                $actualTotals[$componentId] = $qtyPerUnit * $qty * ($ratio[$componentId] ?? 1.0);
            }

            $this->writeComponentSnapshot(
                $row['detail'],
                $row['product'],
                [],            // tanpa substitusi
                $qty,
                $row['perUnit'],
                $actualTotals
            );
        }
    }

    /**
     * Kurangi stok komponen yang terhubung ke modifier.
     *
     * Dipanggil untuk setiap modifier yang memiliki component_id.
     * totalQty = modifier_qty × item_qty_in_cart
     *
     * @throws InsufficientStockException
     */
    public function deductForModifier(
        int $modifierId,
        float $totalQty,
        int $transactionId,
        int $userId
    ): void {
        $modifier = Modifier::find($modifierId);

        if (!$modifier || !$modifier->component_id) {
            return; // Modifier tidak terhubung ke komponen, skip
        }

        $component = Component::lockForUpdate()->find($modifier->component_id);

        if (!$component) {
            Log::warning("Modifier [{$modifierId}] mereferensikan component_id [{$modifier->component_id}] yang tidak ditemukan.");
            return;
        }

        $newStock = $component->stock - $totalQty;

        if ($newStock < 0) {
            throw new InsufficientStockException(
                "Stok komponen '{$component->name}' tidak cukup untuk modifier '{$modifier->name}'. "
                . "Dibutuhkan: {$totalQty} {$component->unit?->symbol}, "
                . "Tersisa: {$component->stock} {$component->unit?->symbol}."
            );
        }

        $component->update(['stock' => $newStock]);

        ComponentStockLog::record(
            componentId:   $component->id,
            userId:        $userId,
            type:          'modifier_deduct',
            amount:        -$totalQty,
            finalStock:    $newStock,
            note:          "Modifier '{$modifier->name}' × {$totalQty}: Transaksi #{$transactionId}",
            referenceId:   $transactionId,
            referenceType: 'transaction',
        );

        if ($component->fresh()->isLowStock()) {
            Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit?->symbol} (min: {$component->minimum_stock})");
        }
    }

    // -----------------------------------------------------------------
    // Return Operations
    // -----------------------------------------------------------------

    /**
     * Kembalikan stok komponen (BOM produk & modifier ber-component) untuk satu
     * baris retur. Dipanggil dari PosCheckout::processReturn() di dalam
     * DB::transaction() yang sama dengan pembuatan ProductReturn.
     *
     * Sengaja tidak melempar exception — stok hanya bertambah di sini, tidak
     * ada skenario "tidak cukup stok" untuk pengembalian.
     */
    public function restoreForReturn(
        int $transactionDetailId,
        float $returnedQty,
        ?int $userId,
        int $returnId
    ): void {
        $detail = TransactionDetail::with(['product.bom', 'modifiers', 'components'])->find($transactionDetailId);

        if (!$detail || $returnedQty <= 0) {
            return;
        }

        if ($detail->components->isNotEmpty()) {
            // Jalur utama: kembalikan persis komposisi yang dulu dipotong.
            // Memakai quantity_total (yang benar-benar terpotong), BUKAN
            // quantity_per_unit — pada penjualan oversold lewat settlement QRIS
            // keduanya berbeda, dan mengembalikan lebih dari yang pernah dipotong
            // akan menciptakan stok hantu.
            $soldQty = max(1, (int) $detail->quantity);
            $ratio   = min(1.0, $returnedQty / $soldQty);

            foreach ($detail->components as $snapshot) {
                $component = Component::lockForUpdate()->find($snapshot->component_id);

                if (!$component) {
                    Log::warning("Retur: Komponen ID [{$snapshot->component_id}] pada snapshot Transaksi Detail #{$transactionDetailId} tidak ditemukan.");
                    continue;
                }

                $restored = round((float) $snapshot->quantity_total * $ratio, 3);

                if ($restored <= 0) {
                    continue;
                }

                $newStock = $component->stock + $restored;
                $component->update(['stock' => $newStock]);

                ComponentStockLog::record(
                    componentId:   $component->id,
                    userId:        $userId,
                    type:          'return_add',
                    amount:        $restored,
                    finalStock:    $newStock,
                    note:          "Retur: Transaksi Detail #{$transactionDetailId} (Retur #{$returnId})"
                                   . ($snapshot->isSubstitute() ? ' — komponen pengganti' : ''),
                    referenceId:   $returnId,
                    referenceType: 'return',
                );
            }
        } elseif ($detail->product && $detail->product->hasBom()) {
            // FALLBACK untuk transaksi lama (dibuat sebelum tabel snapshot ada).
            // Perilakunya sengaja dipertahankan persis seperti sebelumnya. Jangan
            // di-backfill dari BOM yang hidup — BOM bisa sudah berubah sejak dijual.
            foreach ($detail->product->bom as $bom) {
                $component = Component::lockForUpdate()->find($bom->component_id);

                if (!$component) {
                    Log::warning("Retur: Komponen ID [{$bom->component_id}] dalam BOM produk ID [{$detail->product_id}] tidak ditemukan.");
                    continue;
                }

                $restored = $bom->quantity * $returnedQty;
                $newStock = $component->stock + $restored;
                $component->update(['stock' => $newStock]);

                ComponentStockLog::record(
                    componentId:   $component->id,
                    userId:        $userId,
                    type:          'return_add',
                    amount:        $restored,
                    finalStock:    $newStock,
                    note:          "Retur: Transaksi Detail #{$transactionDetailId} (Retur #{$returnId})",
                    referenceId:   $returnId,
                    referenceType: 'return',
                );
            }
        }

        foreach ($detail->modifiers as $modifier) {
            if (!$modifier->component_id) {
                continue;
            }

            $component = Component::lockForUpdate()->find($modifier->component_id);

            if (!$component) {
                Log::warning("Retur: Modifier [{$modifier->id}] mereferensikan component_id [{$modifier->component_id}] yang tidak ditemukan.");
                continue;
            }

            $restored = (float) $modifier->pivot->quantity * $returnedQty;
            $newStock = $component->stock + $restored;
            $component->update(['stock' => $newStock]);

            ComponentStockLog::record(
                componentId:   $component->id,
                userId:        $userId,
                type:          'return_add',
                amount:        $restored,
                finalStock:    $newStock,
                note:          "Retur: Modifier '{$modifier->name}' × {$restored} — Transaksi Detail #{$transactionDetailId} (Retur #{$returnId})",
                referenceId:   $returnId,
                referenceType: 'return',
            );
        }
    }

    // -----------------------------------------------------------------
    // Best-Effort Variants (khusus settlement webhook Midtrans)
    // -----------------------------------------------------------------

    /**
     * Versi best-effort dari deductForBom() — KHUSUS dipakai saat memproses settlement
     * webhook Midtrans (POS QRIS), bukan checkout kasir biasa.
     *
     * Bedanya: uang customer di jalur ini SUDAH PASTI masuk sebelum method ini dipanggil,
     * jadi TIDAK BOLEH sampai melempar exception yang membatalkan (rollback) seluruh
     * pencatatan pembayaran hanya karena stok komponen kurang — itu akan membuat transaksi
     * yang sudah dibayar hilang dari sistem tanpa jejak. Sebagai gantinya: kurangi stok
     * yang TERSEDIA SEKARANG (floor di 0, tidak pernah minus), dan catat CRITICAL kalau
     * ternyata kurang supaya staf tahu ada potensi oversold yang perlu ditindaklanjuti manual.
     */
    public function deductForBomBestEffort(
        int $productId,
        int $productQty,
        int $transactionId,
        int $userId,
        array $subMap = [],
        ?TransactionDetail $detail = null
    ): void {
        $product = Product::with('bom.component')->find($productId);

        if (!$product) {
            Log::error("BOM best-effort: Produk ID [{$productId}] tidak ditemukan, Transaksi #{$transactionId}.");
            return;
        }

        $perUnit = app(ComponentDemandResolver::class)->demandPerUnit($product, $subMap);

        $componentIds = array_keys($perUnit);
        sort($componentIds); // urutan lock deterministik, cegah deadlock

        $actualTotals = [];

        foreach ($componentIds as $componentId) {
            $needed    = $perUnit[$componentId] * $productQty;
            $component = Component::lockForUpdate()->find($componentId);

            if (!$component) {
                Log::error("BOM best-effort: Komponen ID [{$componentId}] tidak ditemukan untuk produk ID [{$productId}], Transaksi #{$transactionId}.");
                continue;
            }

            $current = (float) $component->stock;
            $deduct  = min($needed, max(0, $current));

            // Simpan yang BENAR-BENAR terpotong — dipakai sebagai quantity_total di
            // snapshot supaya retur tidak mengembalikan lebih dari yang pernah keluar.
            $actualTotals[$componentId] = $deduct;

            if ($deduct < $needed) {
                Log::critical(
                    "OVERSOLD (settlement QRIS): Komponen '{$component->name}' butuh {$needed} {$component->unit?->symbol} untuk BOM Transaksi #{$transactionId}, " .
                    "tersisa hanya {$current} {$component->unit?->symbol}. Barang tetap harus dibuatkan, cek ketersediaan fisik secara manual."
                );
            }

            $newStock = $current - $deduct;
            $component->update(['stock' => $newStock]);

            ComponentStockLog::record(
                componentId:   $component->id,
                userId:        $userId,
                type:          'bom_deduct',
                amount:        -$deduct,
                finalStock:    $newStock,
                note:          "BOM Deduct (settlement QRIS): Transaksi #{$transactionId}",
                referenceId:   $transactionId,
                referenceType: 'transaction',
            );

            if ($component->fresh()->isLowStock()) {
                Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit?->symbol} (min: {$component->minimum_stock})");
            }
        }

        $this->writeComponentSnapshot($detail, $product, $subMap, $productQty, $perUnit, $actualTotals);
    }

    /**
     * Versi best-effort dari deductForModifier() — lihat catatan di deductForBomBestEffort().
     */
    public function deductForModifierBestEffort(
        int $modifierId,
        float $totalQty,
        int $transactionId,
        int $userId
    ): void {
        $modifier = Modifier::find($modifierId);

        if (!$modifier || !$modifier->component_id) {
            return; // Modifier tidak terhubung ke komponen, skip
        }

        $component = Component::lockForUpdate()->find($modifier->component_id);

        if (!$component) {
            Log::warning("Modifier [{$modifierId}] mereferensikan component_id [{$modifier->component_id}] yang tidak ditemukan.");
            return;
        }

        $current = (float) $component->stock;
        $deduct  = min($totalQty, max(0, $current));

        if ($deduct < $totalQty) {
            Log::critical(
                "OVERSOLD (settlement QRIS): Komponen '{$component->name}' butuh {$totalQty} {$component->unit?->symbol} untuk modifier '{$modifier->name}' Transaksi #{$transactionId}, " .
                "tersisa hanya {$current} {$component->unit?->symbol}. Barang tetap harus dibuatkan, cek ketersediaan fisik secara manual."
            );
        }

        $newStock = $current - $deduct;
        $component->update(['stock' => $newStock]);

        ComponentStockLog::record(
            componentId:   $component->id,
            userId:        $userId,
            type:          'modifier_deduct',
            amount:        -$deduct,
            finalStock:    $newStock,
            note:          "Modifier '{$modifier->name}' × {$totalQty} (settlement QRIS): Transaksi #{$transactionId}",
            referenceId:   $transactionId,
            referenceType: 'transaction',
        );

        if ($component->fresh()->isLowStock()) {
            Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit?->symbol} (min: {$component->minimum_stock})");
        }
    }

    // -----------------------------------------------------------------
    // Availability Check (bukan modifikasi stok)
    // -----------------------------------------------------------------

    /**
     * Cek ketersediaan stok BOM untuk sejumlah produk.
     * Return array of errors (kosong = semua stok cukup).
     *
     * Digunakan untuk soft-check di POS saat addToCart.
     * Tidak menggunakan lockForUpdate — bukan operasi tulis.
     */
    public function checkBomAvailability(int $productId, int $qty, array $subMap = []): array
    {
        // Didelegasikan ke ComponentDemandResolver supaya perhitungannya identik
        // dengan yang dipakai tampilan POS dan validasi checkout. Bentuk return
        // dipertahankan ('component','needed','stock','unit') agar pemanggil lama
        // tidak perlu diubah.
        $shortfalls = app(ComponentDemandResolver::class)->shortfalls([
            [
                'product_id'    => $productId,
                'quantity'      => $qty,
                'modifiers'     => [],
                'substitutions' => $subMap,
            ],
        ]);

        return array_map(fn (array $s) => [
            'component' => $s['component'],
            'needed'    => $s['needed'],
            'stock'     => $s['available'],
            'unit'      => $s['unit'],
        ], $shortfalls);
    }

    /**
     * Cek ketersediaan stok komponen untuk modifier.
     * Digunakan saat kasir memilih modifier di POS (soft check).
     */
    public function checkModifierAvailability(int $modifierId, float $qty): array
    {
        $errors   = [];
        $modifier = Modifier::with(['component' => fn ($q) => $q->select('id', 'name', 'stock', 'unit_id')->with('unit')])->find($modifierId);

        if (!$modifier || !$modifier->component_id || !$modifier->component) {
            return $errors;
        }

        if ($modifier->component->stock < $qty) {
            $errors[] = [
                'component' => $modifier->component->name,
                'needed'    => $qty,
                'stock'     => $modifier->component->stock,
                'unit'      => $modifier->component->unit?->symbol,
            ];
        }

        return $errors;
    }

    // -----------------------------------------------------------------
    // Production (Repacking) Operations
    // -----------------------------------------------------------------

    /**
     * Tambah stok komponen dari hasil repacking/produksi.
     * Update HPP menggunakan metode Weighted Average Cost.
     *
     * @throws \RuntimeException
     */
    public function addFromProduction(
        int $componentId,
        float $quantity,
        float $unitCost,
        int $productionId,
        int $userId
    ): Component {
        $component = Component::lockForUpdate()->findOrFail($componentId);
        $oldStock  = $component->stock;
        $newStock  = $oldStock + $quantity;

        // Weighted Average HPP
        $newCost = $newStock > 0
            ? (($oldStock * $component->cost_price) + ($quantity * $unitCost)) / $newStock
            : $unitCost;

        $component->update([
            'stock'      => $newStock,
            'cost_price' => round($newCost, 4),
        ]);

        ComponentStockLog::record(
            componentId:   $componentId,
            userId:        $userId,
            type:          'production_add',
            amount:        $quantity,
            finalStock:    $newStock,
            note:          "Hasil Repacking Batch: Produksi #{$productionId}",
            referenceId:   $productionId,
            referenceType: 'production',
        );

        return $component->fresh();
    }

    // -----------------------------------------------------------------
    // Manual Adjustment
    // -----------------------------------------------------------------

    /**
     * Penyesuaian stok manual oleh admin.
     */
    public function adjustStock(
        int $componentId,
        string $type, // 'add', 'sub', 'set'
        float $amount,
        int $userId,
        ?string $note = null
    ): Component {
        $component = Component::lockForUpdate()->findOrFail($componentId);
        $oldStock  = $component->stock;

        $newStock = match ($type) {
            'add' => $oldStock + $amount,
            'sub' => $oldStock - $amount,
            'set' => $amount,
            default => throw new \InvalidArgumentException("Type penyesuaian tidak valid: {$type}"),
        };

        if ($newStock < 0) {
            throw new InsufficientStockException(
                "Pengurangan stok melebihi stok yang ada. Stok saat ini: {$oldStock} {$component->unit?->symbol}."
            );
        }

        $component->update(['stock' => $newStock]);

        ComponentStockLog::record(
            componentId:   $componentId,
            userId:        $userId,
            type:          'adjustment',
            amount:        $newStock - $oldStock,
            finalStock:    $newStock,
            note:          $note ?? "Penyesuaian manual ({$type})",
            referenceId:   null,
            referenceType: null,
        );

        return $component->fresh();
    }
}
