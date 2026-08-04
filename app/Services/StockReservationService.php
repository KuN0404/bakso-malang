<?php

namespace App\Services;

use App\Exceptions\InsufficientStockException;
use App\Models\Component;
use App\Models\ComponentStockLog;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\SelfOrder;
use App\Models\StockLog;
use App\Models\StockReservation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * StockReservationService
 *
 * Mengelola siklus hidup reservation stok untuk Self Order:
 *  - reserve()   → Buat reservation saat order dibuat
 *  - release()   → Lepas reservation saat order cancelled/expired
 *  - convert()   → Konversi reservation ke actual stock reduction saat settlement
 *
 * Semua operasi tulis HARUS dipanggil dalam DB::transaction().
 */
class StockReservationService
{
    // -----------------------------------------------------------------
    // Reservation Creation
    // -----------------------------------------------------------------

    /**
     * Reserve stok untuk seluruh cart Self Order.
     *
     * Proses:
     *  1. Untuk setiap item cart:
     *     - Jika produk punya BOM → reserve komponen sesuai BOM
     *     - Jika produk tidak punya BOM → reserve produk langsung
     *  2. Untuk setiap modifier yang terhubung komponen → reserve komponen
     *
     * @throws InsufficientStockException
     */
    public function reserveForSelfOrder(SelfOrder $selfOrder, array $cartItems, Carbon $expiresAt): void
    {
        foreach ($cartItems as $item) {
            $product = Product::with('bom.component')->lockForUpdate()->find($item['product_id']);

            if (!$product) {
                throw new \RuntimeException("Produk ID [{$item['product_id']}] tidak ditemukan.");
            }

            if ($product->hasBom()) {
                // Mode BOM: reserve komponen
                $this->reserveComponentsForBom(
                    selfOrder: $selfOrder,
                    productId: $item['product_id'],
                    quantity:  $item['quantity'],
                    expiresAt: $expiresAt
                );
            } elseif ($product->track_stock) {
                // Mode Direct: reserve produk langsung
                $this->reserveProduct(
                    selfOrder:  $selfOrder,
                    product:    $product,
                    quantity:   $item['quantity'],
                    expiresAt:  $expiresAt
                );
            }

            // Reserve modifier components
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifierData) {
                    if (!empty($modifierData['component_id'])) {
                        $totalModQty = ($modifierData['quantity'] ?? 1) * $item['quantity'];
                        $this->reserveComponent(
                            selfOrder:   $selfOrder,
                            componentId: (int) $modifierData['component_id'],
                            quantity:    (float) $totalModQty,
                            expiresAt:   $expiresAt
                        );
                    }
                }
            }
        }
    }

    /**
     * Reserve stok produk langsung (non-BOM).
     *
     * @throws InsufficientStockException
     */
    private function reserveProduct(
        SelfOrder $selfOrder,
        Product $product,
        int $quantity,
        Carbon $expiresAt
    ): void {
        $availableStock = $product->getAvailableStock();

        if ($availableStock < $quantity) {
            throw new InsufficientStockException(
                "Stok '{$product->name}' tidak cukup. " .
                "Tersedia: {$availableStock}, Dibutuhkan: {$quantity}."
            );
        }

        StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'product',
            'item_id'         => $product->id,
            'quantity'        => $quantity,
            'status'          => 'active',
            'expires_at'      => $expiresAt,
        ]);

        Log::channel('self_order')->info("StockReservation: Product [{$product->name}] ×{$quantity} reserved untuk SelfOrder [{$selfOrder->id}].");
    }

    /**
     * Reserve komponen-komponen BOM untuk suatu produk.
     *
     * @throws InsufficientStockException
     */
    private function reserveComponentsForBom(
        SelfOrder $selfOrder,
        int $productId,
        int $quantity,
        Carbon $expiresAt
    ): void {
        $bomItems = ProductBom::where('product_id', $productId)->with('component')->get();

        foreach ($bomItems as $bom) {
            $needed = $bom->quantity * $quantity;
            $this->reserveComponent($selfOrder, $bom->component_id, $needed, $expiresAt);
        }
    }

    /**
     * Reserve stok komponen.
     *
     * @throws InsufficientStockException
     */
    private function reserveComponent(
        SelfOrder $selfOrder,
        int $componentId,
        float $quantity,
        Carbon $expiresAt
    ): void {
        $component = Component::lockForUpdate()->find($componentId);

        if (!$component) {
            throw new \RuntimeException("Komponen ID [{$componentId}] tidak ditemukan.");
        }

        $activeReservations = StockReservation::getTotalActiveForComponent($componentId);
        $availableStock     = $component->stock - $activeReservations;

        if ($availableStock < $quantity) {
            throw new InsufficientStockException(
                "Stok komponen '{$component->name}' tidak cukup. " .
                "Tersedia: {$availableStock} {$component->unit}, Dibutuhkan: {$quantity} {$component->unit}."
            );
        }

        StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'component',
            'item_id'         => $componentId,
            'quantity'        => $quantity,
            'status'          => 'active',
            'expires_at'      => $expiresAt,
        ]);

        Log::channel('self_order')->info("StockReservation: Component [{$component->name}] ×{$quantity} reserved untuk SelfOrder [{$selfOrder->id}].");
    }

    // -----------------------------------------------------------------
    // Release Reservations (Cancel / Expired)
    // -----------------------------------------------------------------

    /**
     * Lepas semua reservation untuk Self Order (cancel/expired).
     * Stok fisik TIDAK berubah — hanya reservation yang dihapus.
     */
    public function releaseForSelfOrder(SelfOrder $selfOrder): void
    {
        $released = StockReservation::releaseAllForOrder(SelfOrder::class, $selfOrder->id);

        Log::channel('self_order')->info(
            "StockReservation: {$released} reservation dilepas untuk SelfOrder [{$selfOrder->id}]."
        );
    }

    // -----------------------------------------------------------------
    // Convert to Actual Stock Reduction (Settlement)
    // -----------------------------------------------------------------

    /**
     * Konversi reservation ke penjualan aktual.
     * Dipanggil saat QRIS settlement atau kasir konfirmasi bayar.
     *
     * Proses:
     *  1. Mark reservation sebagai 'converted'
     *  2. Kurangi stok fisik (product.stock atau component.stock)
     *  3. Catat StockLog / ComponentStockLog
     *
     * @throws \Exception
     */
    public function convertForSelfOrder(
        SelfOrder $selfOrder,
        ?int $actorUserId,
        int $transactionId,
        string $invoiceNumber
    ): void {
        // Ambil dan lock semua reservation aktif
        $reservations = StockReservation::getActiveForOrderLocked(SelfOrder::class, $selfOrder->id);

        if ($reservations->isEmpty() && $this->hadReleasedReservations($selfOrder)) {
            // Settlement Midtrans TERLAMBAT: CheckExpiredSelfOrderReservationsJob sudah kadung
            // melepas reservasi (stoknya bisa saja sudah diambil order lain). Uang customer
            // sudah benar-benar masuk lewat Midtrans — order TETAP harus jadi Paid — tapi kalau
            // kita diam saja di sini, stok TIDAK PERNAH dikurangi sama sekali untuk order ini,
            // padahal barangnya tetap harus dibuat/diserahkan. Kurangi ulang secara best-effort
            // dari item yang tersimpan permanen di self_order_items (bukan dari reservasi yang
            // sudah hilang), dan catat CRITICAL kalau ternyata stok sudah tidak cukup (oversold).
            $this->convertLateSettlementFromItems($selfOrder, $actorUserId, $transactionId, $invoiceNumber);
            return;
        }

        foreach ($reservations as $reservation) {
            if ($reservation->item_type === 'product') {
                $this->convertProductReservation($reservation, $actorUserId, $invoiceNumber);
            } else {
                $this->convertComponentReservation($reservation, $actorUserId, $transactionId, $invoiceNumber);
            }
        }

        // Mark semua reservation sebagai converted
        StockReservation::convertAllForOrder(SelfOrder::class, $selfOrder->id);

        Log::channel('self_order')->info(
            "StockReservation: {$reservations->count()} reservation dikonversi untuk SelfOrder [{$selfOrder->id}], Transaction [{$transactionId}]."
        );
    }

    private function hadReleasedReservations(SelfOrder $selfOrder): bool
    {
        return StockReservation::where('reservable_type', SelfOrder::class)
            ->where('reservable_id', $selfOrder->id)
            ->where('status', 'released')
            ->exists();
    }

    /**
     * Fallback untuk settlement Midtrans yang datang setelah reservasi sudah dilepas job expiry.
     * Merekonstruksi kebutuhan stok dari self_order_items (data permanen, bukan reservasi yang
     * sudah hilang) dan mengurangi stok yang TERSEDIA SEKARANG secara best-effort — tidak pernah
     * sampai negatif, tapi setiap kekurangan dicatat CRITICAL untuk ditindaklanjuti staf (order
     * tetap perlu dibuatkan barangnya meski stok sistem sudah menipis/habis karena diambil order lain).
     */
    private function convertLateSettlementFromItems(
        SelfOrder $selfOrder,
        ?int $actorUserId,
        int $transactionId,
        string $invoiceNumber
    ): void {
        $selfOrder->loadMissing('items.modifiers.modifier');

        foreach ($selfOrder->items as $item) {
            $product = Product::with('bom.component')->lockForUpdate()->find($item->product_id);

            if ($product) {
                if ($product->hasBom()) {
                    foreach ($product->bom as $bom) {
                        $needed = $bom->quantity * $item->quantity;
                        $this->deductComponentBestEffort($bom->component_id, $needed, $actorUserId, $transactionId, $invoiceNumber, $selfOrder->id);
                    }
                } elseif ($product->track_stock) {
                    $this->deductProductBestEffort($product, $item->quantity, $actorUserId, $invoiceNumber, $selfOrder->id);
                }
            }

            foreach ($item->modifiers as $mod) {
                $componentId = $mod->modifier?->component_id;
                if ($componentId) {
                    $needed = $mod->quantity * $item->quantity;
                    $this->deductComponentBestEffort($componentId, $needed, $actorUserId, $transactionId, $invoiceNumber, $selfOrder->id);
                }
            }
        }

        Log::channel('self_order')->warning(
            "StockReservation: SelfOrder [{$selfOrder->id}] settlement TERLAMBAT — reservasi sudah kadung dilepas job expiry. " .
            "Stok dikurangi ulang secara best-effort dari item tersimpan (cek log CRITICAL untuk kemungkinan oversold)."
        );
    }

    private function deductProductBestEffort(Product $product, int $qty, ?int $userId, string $invoiceNumber, int $selfOrderId): void
    {
        $current = (int) $product->stock;
        $deduct  = min($qty, max(0, $current));

        if ($deduct < $qty) {
            Log::channel('self_order')->critical(
                "OVERSOLD (settlement terlambat): Produk '{$product->name}' butuh {$qty}, stok tersisa hanya {$current}. " .
                "SelfOrder [{$selfOrderId}] — barang tetap harus dibuatkan, cek ketersediaan fisik secara manual."
            );
        }

        $newStock = $current - $deduct;
        $product->update(['stock' => $newStock]);

        StockLog::record(
            productId:  $product->id,
            userId:     $userId,
            type:       'self_order_sale',
            amount:     -$deduct,
            finalStock: $newStock,
            note:       "Self Order Inv: {$invoiceNumber} (settlement terlambat)"
        );
    }

    private function deductComponentBestEffort(
        int $componentId,
        float $qty,
        ?int $userId,
        int $transactionId,
        string $invoiceNumber,
        int $selfOrderId
    ): void {
        $component = Component::lockForUpdate()->find($componentId);
        if (!$component) return;

        $current = (float) $component->stock;
        $deduct  = min($qty, max(0, $current));

        if ($deduct < $qty) {
            Log::channel('self_order')->critical(
                "OVERSOLD (settlement terlambat): Komponen '{$component->name}' butuh {$qty} {$component->unit}, tersisa hanya {$current} {$component->unit}. " .
                "SelfOrder [{$selfOrderId}] — barang tetap harus dibuatkan, cek ketersediaan fisik secara manual."
            );
        }

        $newStock = $current - $deduct;
        $component->update(['stock' => $newStock]);

        ComponentStockLog::record(
            componentId:   $component->id,
            userId:        $userId,
            type:          'self_order_deduct',
            amount:        -$deduct,
            finalStock:    $newStock,
            note:          "Self Order Inv: {$invoiceNumber} (settlement terlambat)",
            referenceId:   $transactionId,
            referenceType: 'transaction'
        );
    }

    private function convertProductReservation(
        StockReservation $reservation,
        ?int $userId,
        string $invoiceNumber
    ): void {
        $product = Product::lockForUpdate()->find($reservation->item_id);

        if (!$product) {
            Log::channel('self_order')->error("Convert reservation: Product [{$reservation->item_id}] tidak ditemukan.");
            return;
        }

        $product->decrement('stock', $reservation->quantity);

        StockLog::record(
            productId:  $product->id,
            userId:     $userId,
            type:       'self_order_sale',
            amount:     -(int) $reservation->quantity,
            finalStock: $product->fresh()->stock,
            note:       "Self Order Inv: {$invoiceNumber}"
        );
    }

    private function convertComponentReservation(
        StockReservation $reservation,
        ?int $userId,
        int $transactionId,
        string $invoiceNumber
    ): void {
        $component = Component::lockForUpdate()->find($reservation->item_id);

        if (!$component) {
            Log::channel('self_order')->error("Convert reservation: Component [{$reservation->item_id}] tidak ditemukan.");
            return;
        }

        $newStock = $component->stock - $reservation->quantity;

        if ($newStock < 0) {
            // Race condition: stok sudah habis (sangat jarang terjadi karena ada reservation)
            Log::channel('self_order')->critical(
                "Convert reservation: Stok komponen [{$component->name}] negatif setelah konversi! " .
                "SelfOrder ID: {$reservation->reservable_id}"
            );
            $newStock = 0;
        }

        $component->update(['stock' => $newStock]);

        ComponentStockLog::record(
            componentId:   $component->id,
            userId:        $userId,
            type:          'self_order_deduct',
            amount:        -$reservation->quantity,
            finalStock:    $newStock,
            note:          "Self Order Inv: {$invoiceNumber}",
            referenceId:   $transactionId,
            referenceType: 'transaction'
        );
    }

    // -----------------------------------------------------------------
    // Validation Helpers (tanpa lock, untuk UI check saja)
    // -----------------------------------------------------------------

    /**
     * Cek apakah cart bisa direservasi (soft check, tanpa lock).
     * Gunakan ini untuk validasi tampilan, bukan untuk proses checkout.
     *
     * @return array [product_id => error_message]
     */
    public function validateCartAvailability(array $cartItems): array
    {
        $errors = [];

        foreach ($cartItems as $item) {
            $product = Product::with('bom.component')->find($item['product_id']);

            if (!$product || !$product->is_active) {
                $errors[$item['product_id']] = "Produk tidak tersedia.";
                continue;
            }

            if ($product->hasBom()) {
                // Cek stok komponen BOM
                foreach ($product->bom as $bom) {
                    $needed    = $bom->quantity * $item['quantity'];
                    $available = $bom->component->stock - StockReservation::getTotalActiveForComponent($bom->component_id);
                    if ($available < $needed) {
                        $errors[$item['product_id']] = "Stok '{$product->name}' tidak cukup.";
                    }
                }
            } elseif ($product->track_stock) {
                $available = $product->getAvailableStock();
                if ($available < $item['quantity']) {
                    $errors[$item['product_id']] = "Stok '{$product->name}' hanya tersisa {$available}.";
                }
            }
        }

        return $errors;
    }
}
