<?php

namespace App\Events\Payment;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Event dikirim setelah status pembayaran berubah (dari webhook Midtrans).
 */
class PaymentStatusChanged
{
    use Dispatchable;

    public function __construct(
        public readonly string $paymentOrderId,
        public readonly string $status,
        public readonly ?int $transactionId = null,
    ) {}
}
