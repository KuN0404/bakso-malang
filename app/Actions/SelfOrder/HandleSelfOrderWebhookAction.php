<?php

namespace App\Actions\SelfOrder;

use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Enums\SelfOrderStatus;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\SelfOrder;
use App\Models\SelfOrderCartSnapshot;
use App\Models\Shift;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Services\ComponentStockService;
use App\Services\ReportSyncService;
use App\Services\StockReservationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * HandleSelfOrderWebhookAction
 *
 * Memproses webhook Midtrans untuk Self Order secara aman.
 * Berbeda dari HandleMidtransWebhookAction (POS), action ini:
 *  - Tidak membutuhkan created_by (cashier)
 *  - Menggunakan SelfOrderCartSnapshot (DB) sebagai sumber cart data
 *  - Menggunakan StockReservationService untuk konversi reservation → actual stock
 *  - Mengirim notifikasi SSE ke customer
 */
class HandleSelfOrderWebhookAction
{
    public function __construct(
        private readonly StockReservationService $reservationService,
        private readonly ComponentStockService $componentStockService,
        private readonly ReportSyncService $reportSyncService,
    ) {}

    /**
     * @throws \Exception
     */
    public function execute(MidtransWebhookPayload $webhookPayload): ?Transaction
    {
        return DB::transaction(function () use ($webhookPayload) {
            // -- 1. Lock PaymentTransaction (cegah race condition) --
            $paymentTx = PaymentTransaction::where('midtrans_order_id', $webhookPayload->orderId)
                ->where('source', 'self_order')
                ->lockForUpdate()
                ->first();

            if (!$paymentTx) {
                Log::channel('self_order')->warning(
                    "Webhook Self Order: PaymentTransaction tidak ditemukan untuk orderId [{$webhookPayload->orderId}]"
                );
                return null;
            }

            // -- 2. Idempotency check --
            if ($paymentTx->isPaid()) {
                Log::channel('self_order')->info(
                    "Webhook Self Order: PaymentTransaction [{$paymentTx->id}] sudah paid. Diabaikan (idempotent)."
                );
                return $paymentTx->transaction;
            }

            // -- Update raw webhook response --
            $paymentTx->update([
                'midtrans_response'   => $webhookPayload->raw,
                'webhook_received_at' => now(),
            ]);

            // -- 3. Proses berdasarkan status --
            if ($webhookPayload->isSuccess()) {
                return $this->handleSuccess($paymentTx, $webhookPayload);
            }

            if ($webhookPayload->isFailure()) {
                $this->handleFailure($paymentTx, $webhookPayload);
                return null;
            }

            if ($webhookPayload->isExpired()) {
                $this->handleExpiry($paymentTx);
                return null;
            }

            Log::channel('self_order')->info(
                "Webhook Self Order: status [{$webhookPayload->transactionStatus}] untuk order [{$webhookPayload->orderId}] diabaikan."
            );
            return null;
        });
    }

    private function handleSuccess(PaymentTransaction $paymentTx, MidtransWebhookPayload $webhookPayload): Transaction
    {
        // -- Transisi PaymentTransaction → paid --
        $paymentTx->update([
            'status'  => PaymentTransactionStatus::Paid->value,
            'paid_at' => now(),
        ]);

        $paymentTx->statusLogs()->create([
            'from_status'  => PaymentTransactionStatus::Pending->value,
            'to_status'    => PaymentTransactionStatus::Paid->value,
            'triggered_by' => 'webhook',
            'note'         => "Self Order settlement: {$webhookPayload->transactionId}",
            'metadata'     => $webhookPayload->raw,
        ]);

        // -- Lock & transisi SelfOrder → paid --
        $selfOrder = SelfOrder::where('id', $paymentTx->self_order_id)
            ->lockForUpdate()
            ->firstOrFail();

        if ($selfOrder->isCompleted() || $selfOrder->isCancelled() || $selfOrder->isExpired()) {
            // Termasuk kasus "QR sudah kadaluarsa di sisi kita tapi settlement Midtrans tetap
            // masuk" (customer sempat scan tepat sebelum kadaluarsa, atau webhook telat sampai).
            // Uang customer sudah benar-benar masuk, jadi order tetap diloloskan jadi Paid —
            // lihat StockReservationService::convertForSelfOrder() untuk penanganan stok kasus ini.
            Log::channel('self_order')->warning(
                "Webhook Self Order: SelfOrder [{$selfOrder->id}] sudah dalam status final [{$selfOrder->status->value}] saat settlement diterima (kemungkinan pembayaran terlambat). Diproses tetap jadi Paid."
            );
        }

        // Pastikan invoice number ada
        if (!$selfOrder->invoice_number) {
            $selfOrder->update(['invoice_number' => 'SO-' . now()->format('Ymd') . '-' . str_pad($selfOrder->queue_number, 4, '0', STR_PAD_LEFT)]);
            $selfOrder->refresh();
        }

        // -- Ambil cart data dari DB snapshot (lebih aman dari Cache) --
        $cartData = $this->getCartData($paymentTx->midtrans_order_id, $selfOrder);

        // -- Tentukan shift (ambil shift aktif manapun, atau null jika tidak ada) --
        $shift = Shift::where('status', 'open')->latest()->first();

        // -- Buat Transaction POS dari Self Order --
        $transaction = $this->createPosTransaction($paymentTx, $selfOrder, $cartData, $shift);

        // -- Konversi reservation → actual stock reduction --
        // Jangan fallback ke 0 — kolom user_id di stock_logs adalah foreign key nullable,
        // dan user ID 0 tidak pernah valid (akan melanggar constraint jika belum ada kasir
        // yang klaim order & tidak ada shift terbuka saat settlement QRIS terjadi).
        $actorUserId = $selfOrder->processed_by ?? $shift?->user_id;
        $this->reservationService->convertForSelfOrder(
            selfOrder:     $selfOrder,
            actorUserId:   $actorUserId,
            transactionId: $transaction->id,
            invoiceNumber: $selfOrder->invoice_number
        );

        // -- Update SelfOrder → paid --
        $selfOrder->update([
            'status'         => SelfOrderStatus::Paid->value,
            'paid_at'        => now(),
            'transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number,
            'shift_id'       => $shift?->id,
        ]);

        // -- Sync ke laporan --
        $this->reportSyncService->syncTransaction($transaction);

        // -- Kirim email struk jika customer memasukkan email --
        if ($selfOrder->customer_email && config('self_order.send_email_receipt', true)) {
            try {
                \Illuminate\Support\Facades\Mail::to($selfOrder->customer_email)
                    ->queue(new \App\Mail\SelfOrderReceiptMail($selfOrder->load('items.modifiers')));
            } catch (\Exception $e) {
                Log::channel('self_order')->warning("Gagal kirim email struk ke [{$selfOrder->customer_email}]: " . $e->getMessage());
            }
        }

        // -- Hapus cart snapshot dari Cache (snapshot DB tetap untuk audit) --
        Cache::forget("self_order_cart_{$paymentTx->midtrans_order_id}");

        Log::channel('self_order_payment')->info(
            "Self Order [{$selfOrder->id}] berhasil dibayar via QRIS. Transaction [{$transaction->id}] dibuat."
        );

        return $transaction;
    }

    private function handleFailure(PaymentTransaction $paymentTx, MidtransWebhookPayload $webhookPayload): void
    {
        if ($paymentTx->isFinal()) return;

        $paymentTx->transitionTo(
            newStatus:   PaymentTransactionStatus::Failed,
            triggeredBy: 'webhook',
            note:        "Self Order Midtrans: {$webhookPayload->transactionStatus}",
            metadata:    $webhookPayload->raw,
        );

        $this->cancelSelfOrder($paymentTx->self_order_id, "Pembayaran QRIS gagal: {$webhookPayload->transactionStatus}");

        Log::channel('self_order_payment')->warning(
            "Webhook Self Order: PaymentTransaction [{$paymentTx->id}] gagal ({$webhookPayload->transactionStatus})."
        );
    }

    private function handleExpiry(PaymentTransaction $paymentTx): void
    {
        if ($paymentTx->isFinal()) return;

        $paymentTx->transitionTo(
            newStatus:   PaymentTransactionStatus::Expired,
            triggeredBy: 'webhook',
            note:        'QRIS Self Order expired (notifikasi Midtrans)',
        );

        $this->cancelSelfOrder($paymentTx->self_order_id, 'QRIS kadaluarsa');
    }

    private function cancelSelfOrder(int $selfOrderId, string $reason): void
    {
        $selfOrder = SelfOrder::find($selfOrderId);
        if (!$selfOrder || $selfOrder->status->isFinal()) return;

        $selfOrder->update([
            'status'           => SelfOrderStatus::Expired->value,
            'cancelled_reason' => $reason,
            'cancelled_at'     => now(),
        ]);

        // Release stock reservation
        app(StockReservationService::class)->releaseForSelfOrder($selfOrder);
    }

    private function getCartData(string $midtransOrderId, SelfOrder $selfOrder): array
    {
        // Prioritas 1: Cache (hot path)
        $cartData = Cache::get("self_order_cart_{$midtransOrderId}");
        if ($cartData) {
            return $cartData;
        }

        // Prioritas 2: Database snapshot (reliable fallback)
        $snapshot = SelfOrderCartSnapshot::getByOrderId($midtransOrderId);
        if ($snapshot) {
            Log::channel('self_order')->info(
                "Cart data untuk [{$midtransOrderId}] diambil dari DB snapshot (Cache miss)."
            );
            return $snapshot->cart_data;
        }

        // Prioritas 3: Rekonstruksi dari SelfOrder items (last resort)
        Log::channel('self_order')->warning(
            "Cart data untuk [{$midtransOrderId}] tidak ditemukan di Cache maupun DB snapshot. Merekonstruksi dari SelfOrder items."
        );
        return $this->reconstructCartFromSelfOrder($selfOrder);
    }

    private function reconstructCartFromSelfOrder(SelfOrder $selfOrder): array
    {
        $selfOrder->load('items.modifiers');
        $cart = [];

        foreach ($selfOrder->items as $item) {
            $modifiers = [];
            foreach ($item->modifiers as $mod) {
                // Format kanonik — HARUS sama dengan yang dihasilkan PlaceSelfOrderAction::buildAndValidateCart()
                // (list, bukan keyed-by-id, dengan key modifier_name/price_adjustment/quantity).
                $modifiers[] = [
                    'modifier_id'      => $mod->modifier_id,
                    'modifier_name'    => $mod->modifier_name,
                    'price_adjustment' => $mod->price_adjustment,
                    'quantity'         => $mod->quantity,
                    'component_id'     => null, // tidak bisa direkonstruksi dari data tersimpan
                ];
            }

            $cart[] = [
                'product_id'     => $item->product_id,
                'product_name'   => $item->product_name,
                'unit_price'     => $item->unit_price,
                'quantity'       => $item->quantity,
                'modifier_total' => $item->modifier_total,
                'subtotal'       => $item->subtotal,
                'modifiers'      => $modifiers,
            ];
        }

        return [
            'cart'          => $cart,
            'subtotal'      => $selfOrder->subtotal,
            'tax_amount'    => $selfOrder->tax_amount,
            'total'         => $selfOrder->total,
            'customer_name' => $selfOrder->customer_name,
            'order_type'    => $selfOrder->order_type,
            'notes'         => $selfOrder->notes,
            'self_order_id' => $selfOrder->id,
        ];
    }

    private function createPosTransaction(
        PaymentTransaction $paymentTx,
        SelfOrder $selfOrder,
        array $cartData,
        ?Shift $shift
    ): Transaction {
        $paymentSource = PaymentSource::activeForSelfOrder()->where('type', 'qris')->first()
            ?? PaymentSource::activeForSelfOrder()->first();

        $transaction = Transaction::create([
            'user_id'                => $selfOrder->processed_by ?? ($shift?->user_id),
            'shift_id'               => $shift?->id,
            'payment_source_id'      => $paymentSource?->id,
            'payment_transaction_id' => $paymentTx->id,
            'self_order_id'          => $selfOrder->id,
            'invoice_number'         => $selfOrder->invoice_number,
            'queue_number'           => $selfOrder->queue_number,
            'subtotal'               => $cartData['subtotal'],
            'discount_amount'        => 0,
            'tax_amount'             => $cartData['tax_amount'],
            'total'                  => $cartData['total'],
            'paid_amount'            => $cartData['total'],
            'change_amount'          => 0,
            'payment_method'         => 'qris',
            'payment_gateway_status' => 'settlement',
            'source'                 => 'self_order',
            'status'                 => 'completed',
            'customer_name'          => $selfOrder->customer_name,
            'order_type'             => $selfOrder->order_type,
            'notes'                  => $selfOrder->notes,
        ]);

        $paymentTx->update(['transaction_id' => $transaction->id]);

        // Buat TransactionDetails (untuk laporan POS)
        foreach ($cartData['cart'] as $item) {
            $detail = $transaction->details()->create([
                'product_id'     => $item['product_id'],
                'product_name'   => $item['product_name'],
                'unit_price'     => $item['unit_price'],
                'quantity'       => $item['quantity'],
                'modifier_total' => $item['modifier_total'],
                'subtotal'       => $item['subtotal'],
            ]);

            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifierData) {
                    $detail->modifiers()->attach($modifierData['modifier_id'], [
                        'modifier_name'    => $modifierData['modifier_name'],
                        'price_adjustment' => $modifierData['price_adjustment'],
                        'quantity'         => $modifierData['quantity'] ?? 1,
                    ]);
                }
            }
        }

        return $transaction;
    }
}
