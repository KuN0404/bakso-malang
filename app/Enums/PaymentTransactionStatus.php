<?php

namespace App\Enums;

enum PaymentTransactionStatus: string
{
    case Initiated = 'initiated';
    case Pending   = 'pending';
    case Paid      = 'paid';
    case Failed    = 'failed';
    case Expired   = 'expired';
    case Cancelled = 'cancelled';

    /**
     * Status yang bersifat FINAL (tidak bisa berubah lagi).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Expired,
            self::Cancelled,
        ]);
    }

    /**
     * Validasi apakah bisa transisi ke status baru.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::Initiated => in_array($newStatus, [self::Pending, self::Cancelled]),
            self::Pending   => in_array($newStatus, [self::Paid, self::Failed, self::Expired, self::Cancelled]),
            default         => false, // Final states tidak bisa berubah
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Dimulai',
            self::Pending   => 'Menunggu Pembayaran',
            self::Paid      => 'Dibayar',
            self::Failed    => 'Gagal',
            self::Expired   => 'Kadaluarsa',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Initiated => 'gray',
            self::Pending   => 'yellow',
            self::Paid      => 'green',
            self::Failed    => 'red',
            self::Expired   => 'orange',
            self::Cancelled => 'slate',
        };
    }
}
