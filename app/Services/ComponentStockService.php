<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Component;
use App\Models\ComponentStockLog;
use App\Models\Modifier;
use App\Models\ProductBom;
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
        int $userId
    ): void {
        $bomItems = ProductBom::where('product_id', $productId)
            ->with('component')
            ->get();

        foreach ($bomItems as $bom) {
            $needed    = $bom->quantity * $productQty;
            // Re-lock komponen untuk mencegah race condition
            $component = Component::lockForUpdate()->find($bom->component_id);

            if (!$component) {
                throw new \RuntimeException(
                    "Komponen ID [{$bom->component_id}] tidak ditemukan dalam BOM produk ID [{$productId}]."
                );
            }

            $newStock = $component->stock - $needed;

            if ($newStock < 0) {
                throw new InsufficientStockException(
                    "Stok komponen '{$component->name}' tidak cukup. "
                    . "Dibutuhkan: {$needed} {$component->unit}, "
                    . "Tersisa: {$component->stock} {$component->unit}."
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
                Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit} (min: {$component->minimum_stock})");
            }
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
                . "Dibutuhkan: {$totalQty} {$component->unit}, "
                . "Tersisa: {$component->stock} {$component->unit}."
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
            Log::warning("⚠ Stok komponen '{$component->name}' menipis: {$newStock} {$component->unit} (min: {$component->minimum_stock})");
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
    public function checkBomAvailability(int $productId, int $qty): array
    {
        $errors   = [];
        $bomItems = ProductBom::where('product_id', $productId)
            ->with('component:id,name,stock,unit,minimum_stock')
            ->get();

        foreach ($bomItems as $bom) {
            $needed = $bom->quantity * $qty;
            if ($bom->component->stock < $needed) {
                $errors[] = [
                    'component' => $bom->component->name,
                    'needed'    => $needed,
                    'stock'     => $bom->component->stock,
                    'unit'      => $bom->component->unit,
                ];
            }
        }

        return $errors;
    }

    /**
     * Cek ketersediaan stok komponen untuk modifier.
     * Digunakan saat kasir memilih modifier di POS (soft check).
     */
    public function checkModifierAvailability(int $modifierId, float $qty): array
    {
        $errors   = [];
        $modifier = Modifier::with('component:id,name,stock,unit')->find($modifierId);

        if (!$modifier || !$modifier->component_id || !$modifier->component) {
            return $errors;
        }

        if ($modifier->component->stock < $qty) {
            $errors[] = [
                'component' => $modifier->component->name,
                'needed'    => $qty,
                'stock'     => $modifier->component->stock,
                'unit'      => $modifier->component->unit,
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
                "Pengurangan stok melebihi stok yang ada. Stok saat ini: {$oldStock} {$component->unit}."
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
