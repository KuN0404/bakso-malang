<?php

namespace App\Http\Controllers\Payment;

use App\DTOs\Payment\MidtransWebhookPayload;
use App\Actions\SelfOrder\HandleSelfOrderWebhookAction;
use App\Http\Controllers\Controller;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * SelfOrderMidtransWebhookController
 *
 * Endpoint webhook Midtrans khusus Self Order.
 * Dipisahkan dari POS webhook agar logging dan handler berbeda.
 *
 * Middleware 'midtrans.signature' memvalidasi signature sebelum request masuk.
 */
class SelfOrderMidtransWebhookController extends Controller
{
    public function __construct(
        private readonly HandleSelfOrderWebhookAction $action,
        private readonly MidtransService $midtransService,
    ) {}

    public function __invoke(Request $request): Response
    {
        $data = $request->all();

        Log::channel('self_order_payment')->info(
            "Webhook Self Order diterima: orderId=[{$data['order_id']}] status=[{$data['transaction_status']}]",
            ['payload' => $data]
        );

        // -- Validasi gross amount --
        try {
            $this->midtransService->validateWebhookPayload($data);
        } catch (\InvalidArgumentException $e) {
            Log::channel('self_order_security')->error(
                "Webhook Self Order DITOLAK: {$e->getMessage()}",
                ['payload' => $data]
            );
            return response('Validation failed', 400);
        }

        // -- Proses --
        try {
            $payload = MidtransWebhookPayload::fromArray($data);
            $this->action->execute($payload);
        } catch (\Exception $e) {
            Log::channel('self_order_payment')->error(
                "Webhook Self Order ERROR: {$e->getMessage()}",
                ['orderId' => $data['order_id'] ?? null, 'error' => $e->getMessage()]
            );
            // Return 200 agar Midtrans tidak retry terus (webhook sudah diterima)
            return response('OK', 200);
        }

        return response('OK', 200);
    }
}
