<?php

namespace App\DTOs\Payment;

/**
 * DTO untuk payload webhook dari Midtrans.
 * Dibuat dari raw request body yang sudah diverifikasi signature-nya.
 */
final class MidtransWebhookPayload
{
    public function __construct(
        public readonly string $orderId,
        public readonly string $transactionId,
        public readonly string $transactionStatus,
        public readonly string $paymentType,
        public readonly string $grossAmount,
        public readonly string $statusCode,
        public readonly string $signatureKey,
        public readonly ?string $fraudStatus,
        public readonly array $raw,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            orderId:           $data['order_id'] ?? '',
            transactionId:     $data['transaction_id'] ?? '',
            transactionStatus: $data['transaction_status'] ?? '',
            paymentType:       $data['payment_type'] ?? '',
            grossAmount:       $data['gross_amount'] ?? '0',
            statusCode:        $data['status_code'] ?? '',
            signatureKey:      $data['signature_key'] ?? '',
            fraudStatus:       $data['fraud_status'] ?? null,
            raw:               $data,
        );
    }

    /**
     * Apakah transaksi ini berhasil (settlement) berdasarkan status Midtrans.
     */
    public function isSuccess(): bool
    {
        return $this->transactionStatus === 'settlement'
            || ($this->transactionStatus === 'capture' && $this->fraudStatus === 'accept');
    }

    /**
     * Apakah transaksi ini gagal/deny.
     */
    public function isFailure(): bool
    {
        return in_array($this->transactionStatus, ['deny', 'failure', 'cancel']);
    }

    /**
     * Apakah transaksi ini expired.
     */
    public function isExpired(): bool
    {
        return $this->transactionStatus === 'expire';
    }
}
