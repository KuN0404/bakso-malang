<?php

namespace App\DTOs\Payment;

/**
 * DTO untuk payload cart yang dikirim dari PosCheckout ke Actions.
 * Immutable data object — tidak ada setter.
 */
final class CartPayload
{
    /**
     * @param  array  $cart  Array cart dari Livewire state
     * @param  float  $subtotal
     * @param  float  $taxAmount
     * @param  float  $total
     * @param  float  $paidAmount   Untuk Cash
     * @param  float  $changeAmount Untuk Cash
     * @param  int    $paymentSourceId
     * @param  string $paymentMethod  cash|qris
     * @param  string $customerName
     * @param  string $customerPhone  Opsional
     * @param  string $customerEmail  Opsional
     * @param  string $orderType  dine_in|take_away
     * @param  int|null $serviceAreaId
     * @param  int|null $pagerId
     * @param  string $notes
     * @param  string $idempotencyKey
     */
    public function __construct(
        public readonly array $cart,
        public readonly float $subtotal,
        public readonly float $taxAmount,
        public readonly float $total,
        public readonly float $paidAmount,
        public readonly float $changeAmount,
        public readonly int $paymentSourceId,
        public readonly string $paymentMethod,
        public readonly string $customerName,
        public readonly string $customerPhone,
        public readonly string $customerEmail,
        public readonly string $orderType,
        public readonly ?int $serviceAreaId,
        public readonly ?int $pagerId,
        public readonly string $notes,
        public readonly string $idempotencyKey,
    ) {}

    /**
     * Serialisasi ke array untuk disimpan di Cache (digunakan webhook handler).
     */
    public function toArray(): array
    {
        return [
            'cart'             => $this->cart,
            'subtotal'         => $this->subtotal,
            'tax_amount'       => $this->taxAmount,
            'total'            => $this->total,
            'customer_name'    => $this->customerName,
            'customer_phone'   => $this->customerPhone,
            'customer_email'   => $this->customerEmail,
            'order_type'       => $this->orderType,
            'service_area_id'  => $this->serviceAreaId,
            'pager_id'         => $this->pagerId,
            'notes'            => $this->notes,
        ];
    }
}
