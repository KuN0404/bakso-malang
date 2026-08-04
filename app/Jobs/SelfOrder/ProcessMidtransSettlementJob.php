<?php

namespace App\Jobs\SelfOrder;

use App\Actions\SelfOrder\HandleSelfOrderWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Models\PaymentTransaction;
use App\Services\MidtransService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * ProcessMidtransSettlementJob
 *
 * Dijalankan ketika customer menekan "Cek Status Pembayaran" dan Midtrans
 * sudah mencatat settlement, tetapi webhook belum diterima oleh sistem.
 *
 * Job ini mengambil status terbaru dari Midtrans API dan menjalankan
 * HandleSelfOrderWebhookAction secara aman (idempotent).
 */
class ProcessMidtransSettlementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        public readonly string $midtransOrderId,
    ) {}

    public function handle(
        MidtransService $midtransService,
        HandleSelfOrderWebhookAction $webhookAction,
    ): void {
        // Pastikan PaymentTransaction masih ada dan masih pending
        $paymentTx = PaymentTransaction::where('midtrans_order_id', $this->midtransOrderId)
            ->where('source', 'self_order')
            ->first();

        if (!$paymentTx) {
            Log::channel('self_order_payment')->warning(
                "ProcessMidtransSettlementJob: PaymentTransaction [{$this->midtransOrderId}] tidak ditemukan."
            );
            return;
        }

        // Idempotency: jika sudah paid, tidak perlu diproses lagi
        if ($paymentTx->isPaid()) {
            Log::channel('self_order_payment')->info(
                "ProcessMidtransSettlementJob: PaymentTransaction [{$this->midtransOrderId}] sudah paid. Job diabaikan."
            );
            return;
        }

        try {
            // Cek status terbaru dari Midtrans API
            $midtransData = $midtransService->checkStatus($this->midtransOrderId);
            $txStatus     = $midtransData['transaction_status'] ?? '';

            Log::channel('self_order_payment')->info(
                "ProcessMidtransSettlementJob: Midtrans status [{$txStatus}] untuk order [{$this->midtransOrderId}]."
            );

            if (in_array($txStatus, ['settlement', 'capture'])) {
                $payload = MidtransWebhookPayload::fromArray($midtransData);
                $webhookAction->execute($payload);

                Log::channel('self_order_payment')->info(
                    "ProcessMidtransSettlementJob: HandleSelfOrderWebhookAction berhasil untuk [{$this->midtransOrderId}]."
                );
            } else {
                Log::channel('self_order_payment')->info(
                    "ProcessMidtransSettlementJob: Status [{$txStatus}] bukan settlement. Job selesai tanpa aksi."
                );
            }
        } catch (\Exception $e) {
            Log::channel('self_order_payment')->error(
                "ProcessMidtransSettlementJob ERROR untuk [{$this->midtransOrderId}]: " . $e->getMessage()
            );
            throw $e; // Re-throw agar queue retry bisa bekerja
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('self_order_payment')->error(
            "ProcessMidtransSettlementJob GAGAL PERMANEN untuk [{$this->midtransOrderId}]: " . $exception->getMessage()
        );
    }
}
