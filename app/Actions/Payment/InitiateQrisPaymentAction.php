<?php

namespace App\Actions\Payment;

use App\DTOs\Payment\CartPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\Setting;
use App\Services\CartValidationService;
use App\Services\MidtransService;
use App\Services\ReportSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Membuat transaksi QRIS ke Midtrans dan menyimpan PaymentTransaction.
 *
 * Flow:
 *  1. Hitung ulang harga cart dari DB (tidak percaya subtotal/total dari client)
 *  2. Cancel PaymentTransaction lama untuk invoice yang sama (jika ada)
 *  3. Generate Midtrans Order ID unik
 *  4. Panggil MidtransService::createQrisTransaction() dengan total yang sudah divalidasi
 *  5. Simpan PaymentTransaction (status=pending) + StatusLog + cart snapshot tervalidasi
 */
class InitiateQrisPaymentAction
{
    public function __construct(
        private readonly MidtransService $midtransService,
        private readonly CancelPaymentTransactionAction $cancelAction,
        private readonly CartValidationService $cartValidationService,
        private readonly ReportSyncService $reportSyncService,
    ) {}

    /**
     * @throws \Exception
     */
    public function execute(CartPayload $payload, string $invoiceNumber, int $cashierId): PaymentTransaction
    {
        // -- Hitung ulang harga dari DB — JANGAN percaya subtotal/total dari client --
        $validated = $this->cartValidationService->validateAndPrice($payload->cart, $cashierId);
        $items     = $validated['items'];
        $subtotal  = $validated['subtotal'];

        $taxRate   = (float) Setting::get('tax_percentage', 0, 'general');
        $taxAmount = round($subtotal * ($taxRate / 100), 2);
        $total     = $subtotal + $taxAmount;

        // Cancel transaksi QRIS lama untuk invoice yang sama (edge case: ganti method)
        $existing = PaymentTransaction::getActiveForInvoice($invoiceNumber);
        if ($existing) {
            $this->cancelAction->execute($existing, $cashierId, 'Diganti dengan QRIS baru');
        }

        $expiryMinutes = $this->resolveExpiryMinutes();
        $orderId       = $this->generateOrderId($invoiceNumber);

        // Panggil Midtrans API dengan total yang sudah divalidasi ulang
        $midtransResult = $this->midtransService->createQrisTransaction(
            orderId:       $orderId,
            amount:        $total,
            customerName:  $payload->customerName ?: 'Pelanggan',
            expiryMinutes: $expiryMinutes,
        );

        // Simpan ke database
        return DB::transaction(function () use ($payload, $invoiceNumber, $orderId, $midtransResult, $cashierId, $items, $subtotal, $taxAmount, $total, $expiryMinutes) {
            $paymentTx = PaymentTransaction::create([
                'invoice_number'    => $invoiceNumber,
                'midtrans_order_id' => $orderId,
                'qr_code_url'       => $midtransResult['qr_code_url'],
                'payment_method'    => 'qris',
                'amount'            => $total,
                'status'            => PaymentTransactionStatus::Pending->value,
                'midtrans_response' => $midtransResult['raw'],
                'expired_at'        => $midtransResult['expired_at'],
                'idempotency_key'   => $payload->idempotencyKey ?: Str::uuid()->toString(),
                'created_by'        => $cashierId,
            ]);

            $paymentTx->statusLogs()->create([
                'from_status'  => null,
                'to_status'    => PaymentTransactionStatus::Pending->value,
                'triggered_by' => 'cashier',
                'actor_id'     => $cashierId,
                'note'         => "QRIS dibuat. Berlaku {$expiryMinutes} menit.",
            ]);

            $this->reportSyncService->syncPaymentTransaction($paymentTx);

            // -- Cache cart snapshot yang SUDAH divalidasi untuk direkonstruksi oleh webhook handler --
            Cache::put(
                "qris_cart_{$orderId}",
                [
                    'cart'            => $items,
                    'subtotal'        => $subtotal,
                    'tax_amount'      => $taxAmount,
                    'total'           => $total,
                    'customer_name'   => $payload->customerName,
                    'order_type'      => $payload->orderType,
                    'service_area_id' => $payload->serviceAreaId,
                    'pager_id'        => $payload->pagerId,
                    'notes'           => $payload->notes,
                    'invoice_number'  => $invoiceNumber,
                ],
                now()->addMinutes(30)
            );

            return $paymentTx;
        });
    }

    /**
     * Resolusi durasi QRIS dari Settings, dengan batas min-max dari config.
     */
    private function resolveExpiryMinutes(): int
    {
        $fromSettings = (int) Setting::get('qris_expiry_minutes', config('midtrans.qris_expiry_minutes_default', 5), 'payment');
        $min          = config('midtrans.qris_expiry_minutes_min', 3);
        $max          = config('midtrans.qris_expiry_minutes_max', 15);

        return max($min, min($max, $fromSettings));
    }

    /**
     * Generate Midtrans Order ID yang unik per request (bukan per invoice).
     * Format: INV-YYYYMMDD-XXXX-{random4char}
     */
    private function generateOrderId(string $invoiceNumber): string
    {
        return $invoiceNumber . '-' . strtoupper(Str::random(4)) . '-' . now()->format('His');
    }
}
