<?php

namespace App\Http\Controllers\Payment;

use App\Actions\Payment\HandleMidtransWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Menerima dan memproses webhook dari Midtrans.
 * Middleware VerifyMidtransSignature memverifikasi signature sebelum sampai ke sini.
 */
class MidtransWebhookController
{
    public function __construct(
        private readonly MidtransService $midtransService,
    ) {}

    public function __invoke(Request $request, HandleMidtransWebhookAction $action): JsonResponse
    {
        $data    = $request->all();
        $payload = MidtransWebhookPayload::fromArray($data);

        Log::info('Midtrans Webhook diterima', [
            'order_id'  => $payload->orderId,
            'status'    => $payload->transactionStatus,
            'amount'    => $payload->grossAmount,
        ]);

        // -- Validasi gross_amount terhadap PaymentTransaction di DB (signature saja tidak cukup) --
        try {
            $this->midtransService->validateWebhookPayload($data);
        } catch (\InvalidArgumentException $e) {
            Log::error("Webhook Midtrans DITOLAK: {$e->getMessage()}", ['payload' => $data]);
            return response()->json(['message' => 'Validation failed'], 400);
        }

        try {
            $transaction = $action->execute($payload);

            if ($transaction) {
                // Broadcast event agar SSE Controller bisa mengirim update ke frontend
                event(new \App\Events\Payment\PaymentStatusChanged(
                    paymentOrderId: $payload->orderId,
                    status:         'paid',
                    transactionId:  $transaction->id,
                ));
            }

            return response()->json(['message' => 'OK']);

        } catch (\Exception $e) {
            Log::error('Midtrans Webhook error: ' . $e->getMessage(), [
                'order_id' => $payload->orderId,
                'trace'    => $e->getTraceAsString(),
            ]);

            // Return 200 agar Midtrans tidak retry berkali-kali untuk error yang kita tangani
            return response()->json(['message' => 'Processed with error'], 200);
        }
    }
}
