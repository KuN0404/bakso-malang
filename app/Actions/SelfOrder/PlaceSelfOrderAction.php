<?php

namespace App\Actions\SelfOrder;

use App\Enums\SelfOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\DailyQueueNumber;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SelfOrder;
use App\Models\SelfOrderCartSnapshot;
use App\Models\SelfOrderItem;
use App\Models\Setting;
use App\Services\MidtransService;
use App\Services\StockReservationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use App\Enums\PaymentTransactionStatus;

/**
 * PlaceSelfOrderAction
 *
 * Memproses pembuatan Self Order secara atomik:
 *  1. Validasi idempotency
 *  2. Validasi stok (reservation-aware)
 *  3. Hitung ulang harga di server
 *  4. Buat SelfOrder + Items + Modifiers
 *  5. Generate queue number
 *  6. Reserve stock
 *  7. Jika QRIS: buat PaymentTransaction + QRIS via Midtrans + cart snapshot
 *  8. Jika Cash: langsung masuk waiting_payment
 */
class PlaceSelfOrderAction
{
    public function __construct(
        private readonly StockReservationService $reservationService,
        private readonly MidtransService $midtransService,
    ) {}

    /**
     * @throws InsufficientStockException|\Exception
     */
    public function execute(array $validatedData, string $clientIp): SelfOrder
    {
        // -- Idempotency check --
        $idempotencyKey = $validatedData['idempotency_key'] ?? null;
        if ($idempotencyKey) {
            $existing = SelfOrder::existsByIdempotencyKey($idempotencyKey);
            if ($existing) {
                Log::info("PlaceSelfOrderAction: Idempotency key [{$idempotencyKey}] sudah digunakan, return existing order [{$existing->id}].");
                return $existing->load(['items.modifiers', 'paymentTransaction']);
            }
        }

        // -- Anti-spam: max 3 pending orders per nomor HP --
        $activeCount = SelfOrder::countActivePendingByPhone($validatedData['customer_phone']);
        if ($activeCount >= 3) {
            throw new \RuntimeException('Terlalu banyak pesanan aktif dengan nomor HP ini. Selesaikan atau batalkan pesanan sebelumnya.');
        }

        return DB::transaction(function () use ($validatedData, $clientIp, $idempotencyKey) {
            // -- Ambil & validasi produk dari DB (server-side, tidak percaya frontend) --
            $cartItems       = $this->buildAndValidateCart($validatedData['items']);
            $subtotal        = collect($cartItems)->sum('subtotal');
            // Key 'tax_percentage' — HARUS sama dengan yang dipakai POS (PosCheckout::taxAmount())
            // dan yang disimpan Settings::saveGeneral(). Key 'tax_rate' yang lama tidak pernah
            // di-set di mana pun, jadi pajak Self Order selalu 0% berapa pun admin atur di halaman
            // Pengaturan — bug yang menyebabkan Self Order tidak pernah kena pajak.
            $taxRate         = (float) Setting::get('tax_percentage', 0, 'general');
            $taxAmount       = round($subtotal * ($taxRate / 100), 2);
            $total           = $subtotal + $taxAmount;
            $paymentMethod   = $validatedData['payment_method'];
            $queueNumber     = DailyQueueNumber::getNextNumber();
            $expiryMinutes   = $this->resolveExpiryMinutes($paymentMethod);
            $expiresAt       = now()->addMinutes($expiryMinutes);

            // -- Buat SelfOrder --
            $selfOrder = SelfOrder::create([
                'order_token'      => (string) Str::uuid(),
                'queue_number'     => $queueNumber,
                'customer_name'    => $validatedData['customer_name'],
                'customer_phone'   => $validatedData['customer_phone'],
                'customer_email'   => $validatedData['customer_email'] ?? null,
                'subtotal'         => $subtotal,
                'tax_amount'       => $taxAmount,
                'total'            => $total,
                'order_type'       => $validatedData['order_type'] ?? 'dine_in',
                'notes'            => isset($validatedData['notes']) ? strip_tags($validatedData['notes']) : null,
                'payment_method'   => $paymentMethod,
                'status'           => $paymentMethod === 'qris'
                    ? SelfOrderStatus::PendingPayment->value
                    : SelfOrderStatus::WaitingPayment->value,
                'idempotency_key'  => $idempotencyKey,
                'customer_ip'      => $clientIp,
            ]);

            // -- Buat Items + Modifiers --
            foreach ($cartItems as $cartItem) {
                $item = SelfOrderItem::create([
                    'self_order_id'  => $selfOrder->id,
                    'product_id'     => $cartItem['product_id'],
                    'product_name'   => $cartItem['product_name'],
                    'unit_price'     => $cartItem['unit_price'],
                    'quantity'       => $cartItem['quantity'],
                    'modifier_total' => $cartItem['modifier_total'],
                    'subtotal'       => $cartItem['subtotal'],
                    'notes'          => $cartItem['notes'] ?? null,
                ]);

                foreach ($cartItem['modifiers'] as $modifierData) {
                    $item->modifiers()->create([
                        'modifier_id'      => $modifierData['modifier_id'],
                        'modifier_name'    => $modifierData['modifier_name'],
                        'price_adjustment' => $modifierData['price_adjustment'],
                        'quantity'         => $modifierData['quantity'],
                    ]);
                }
            }

            // -- Reserve stock (dengan lock) --
            $this->reservationService->reserveForSelfOrder($selfOrder, $cartItems, $expiresAt);

            // -- Jika QRIS: buat PaymentTransaction + panggil Midtrans --
            if ($paymentMethod === 'qris') {
                $selfOrder = $this->initiateQrisPayment($selfOrder, $cartItems, $subtotal, $taxAmount, $total, $expiresAt, $expiryMinutes);
            }

            Log::channel('self_order')->info(
                "SelfOrder [{$selfOrder->id}] dibuat. " .
                "Customer: {$selfOrder->customer_name}, " .
                "Method: {$paymentMethod}, Total: {$total}"
            );

            return $selfOrder->load(['items.modifiers', 'paymentTransaction']);
        });
    }

    // -----------------------------------------------------------------
    // Private Helpers
    // -----------------------------------------------------------------

    /**
     * Bangun dan validasi cart dari request (server-side pricing).
     * Harga, status produk, dan stok diambil dari database.
     *
     * @throws InsufficientStockException|\RuntimeException
     */
    private function buildAndValidateCart(array $requestItems): array
    {
        $productIds = array_column($requestItems, 'product_id');

        // Ambil semua produk sekaligus dengan lock (karena akan segera reserve)
        $products = Product::with(['bom.component', 'modifierGroups.modifiers'])
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $cartItems = [];

        foreach ($requestItems as $requestItem) {
            $product = $products->get($requestItem['product_id']);

            if (!$product || !$product->is_active) {
                throw new \RuntimeException("Produk tidak tersedia atau sudah tidak aktif.");
            }

            // Validasi stok (reservation-aware)
            if ($product->track_stock && !$product->hasBom()) {
                $available = $product->getAvailableStock();
                if ($available < $requestItem['quantity']) {
                    throw new InsufficientStockException(
                        "Stok '{$product->name}' tidak cukup. Tersedia: {$available}, Dibutuhkan: {$requestItem['quantity']}."
                    );
                }
            }

            // Hitung modifier total dari DB (jangan percaya frontend)
            $modifiersData    = [];
            $modifierUnitTotal = 0;

            if (!empty($requestItem['modifiers'])) {
                foreach ($requestItem['modifiers'] as $modifierItem) {
                    // Ambil modifier dari relasi
                    $modifier = null;
                    foreach ($product->modifierGroups as $group) {
                        $modifier = $group->modifiers->firstWhere('id', $modifierItem['modifier_id']);
                        if ($modifier) break;
                    }

                    if (!$modifier || !$modifier->is_active) {
                        throw new \RuntimeException("Modifier tidak valid atau tidak aktif.");
                    }

                    $modQty            = max(1, (int) ($modifierItem['quantity'] ?? 1));
                    $modifiersData[]   = [
                        'modifier_id'      => $modifier->id,
                        'modifier_name'    => $modifier->name,
                        'price_adjustment' => $modifier->price_adjustment,
                        'quantity'         => $modQty,
                        'component_id'     => $modifier->component_id,
                    ];
                    $modifierUnitTotal += $modifier->price_adjustment * $modQty;
                }
            }

            $qty      = (int) $requestItem['quantity'];
            $subtotal = ($product->price + $modifierUnitTotal) * $qty;

            $cartItems[] = [
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'unit_price'     => (float) $product->price,
                'quantity'       => $qty,
                'modifier_total' => $modifierUnitTotal,
                'subtotal'       => $subtotal,
                'notes'          => isset($requestItem['notes']) ? strip_tags($requestItem['notes']) : null,
                'modifiers'      => $modifiersData,
            ];
        }

        return $cartItems;
    }

    /**
     * Buat PaymentTransaction QRIS + panggil Midtrans API + simpan cart snapshot.
     */
    private function initiateQrisPayment(
        SelfOrder $selfOrder,
        array $cartItems,
        float $subtotal,
        float $taxAmount,
        float $total,
        Carbon $expiresAt,
        int $expiryMinutes
    ): SelfOrder {
        $invoiceNumber = 'SO-' . now()->format('Ymd') . '-' . str_pad($selfOrder->queue_number, 4, '0', STR_PAD_LEFT);
        $orderId       = $invoiceNumber . '-' . strtoupper(Str::random(4)) . '-' . now()->format('His');

        // Panggil Midtrans QRIS API
        $midtransResult = $this->midtransService->createQrisTransaction(
            orderId:       $orderId,
            amount:        $total,
            customerName:  $selfOrder->customer_name,
            expiryMinutes: $expiryMinutes,
        );

        // Buat PaymentTransaction
        $paymentTx = PaymentTransaction::create([
            'self_order_id'     => $selfOrder->id,
            'invoice_number'    => $invoiceNumber,
            'midtrans_order_id' => $orderId,
            'qr_code_url'       => $midtransResult['qr_code_url'],
            'payment_method'    => 'qris',
            'amount'            => $total,
            'status'            => PaymentTransactionStatus::Pending->value,
            'source'            => 'self_order',
            'midtrans_response' => $midtransResult['raw'],
            'expired_at'        => $midtransResult['expired_at'],
            'idempotency_key'   => (string) Str::uuid(),
            'created_by'        => null, // Self Order tidak punya cashier
        ]);

        $paymentTx->statusLogs()->create([
            'from_status'  => null,
            'to_status'    => PaymentTransactionStatus::Pending->value,
            'triggered_by' => 'self_order',
            'actor_id'     => null,
            'note'         => "QRIS Self Order dibuat. Berlaku {$expiryMinutes} menit.",
        ]);

        // Update SelfOrder dengan payment_transaction_id dan invoice_number
        $selfOrder->update([
            'payment_transaction_id' => $paymentTx->id,
            'invoice_number'         => $invoiceNumber,
        ]);

        // Simpan cart snapshot ke database (TIDAK hanya cache — more reliable)
        $cartData = [
            'cart'          => $cartItems,
            'subtotal'      => $subtotal,
            'tax_amount'    => $taxAmount,
            'total'         => $total,
            'customer_name' => $selfOrder->customer_name,
            'order_type'    => $selfOrder->order_type,
            'notes'         => $selfOrder->notes,
            'self_order_id' => $selfOrder->id,
        ];

        SelfOrderCartSnapshot::createSnapshot(
            midtransOrderId: $orderId,
            selfOrderId:     $selfOrder->id,
            cartData:        $cartData,
            expiresAt:       $expiresAt->addHours(1), // simpan lebih lama dari QRIS expire
        );

        // Juga simpan ke Cache sebagai hot-path (untuk backward compat dan speed)
        \Illuminate\Support\Facades\Cache::put(
            "self_order_cart_{$orderId}",
            $cartData,
            $expiresAt->addHours(1)
        );

        $selfOrder->refresh();
        return $selfOrder;
    }

    private function resolveExpiryMinutes(string $paymentMethod): int
    {
        if ($paymentMethod === 'qris') {
            $fromSettings = (int) Setting::get('qris_expiry_minutes', config('midtrans.qris_expiry_minutes_default', 5), 'payment');
            $min          = config('midtrans.qris_expiry_minutes_min', 3);
            $max          = config('midtrans.qris_expiry_minutes_max', 15);
            return max($min, min($max, $fromSettings));
        }

        // Cash on counter: reservation berlaku 30 menit (standar industri quick service)
        return (int) config('self_order.cash_reservation_timeout_minutes', 30);
    }
}
