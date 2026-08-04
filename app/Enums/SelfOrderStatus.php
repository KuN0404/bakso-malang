<?php

namespace App\Enums;

enum SelfOrderStatus: string
{
    case PendingPayment  = 'pending_payment';   // QRIS: menunggu scan
    case WaitingPayment  = 'waiting_payment';   // Bayar di Tempat: menunggu kasir
    case Paid            = 'paid';              // Sudah dibayar, menunggu proses
    case Processing      = 'processing';        // Dapur sedang proses
    case Ready           = 'ready';             // Siap diambil/disajikan
    case Completed       = 'completed';         // Selesai
    case Cancelled       = 'cancelled';         // Dibatalkan
    case Expired         = 'expired';           // QRIS expired / timeout

    /**
     * Status yang bersifat FINAL (tidak bisa berubah lagi).
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Cancelled,
            self::Expired,
        ]);
    }

    /**
     * Apakah order masih bisa dibatalkan.
     */
    public function isCancellable(): bool
    {
        return in_array($this, [
            self::PendingPayment,
            self::WaitingPayment,
            self::Paid,
        ]);
    }

    /**
     * Apakah order sudah menghasilkan reservation stok.
     */
    public function hasReservation(): bool
    {
        return in_array($this, [
            self::PendingPayment,
            self::WaitingPayment,
            self::Paid,
            self::Processing,
        ]);
    }

    /**
     * Validasi transisi status yang diizinkan.
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match ($this) {
            self::PendingPayment => in_array($newStatus, [self::Paid, self::Cancelled, self::Expired]),
            self::WaitingPayment => in_array($newStatus, [self::Paid, self::Cancelled]),
            self::Paid           => in_array($newStatus, [self::Processing, self::Cancelled]),
            self::Processing     => in_array($newStatus, [self::Ready, self::Cancelled]),
            self::Ready          => in_array($newStatus, [self::Completed, self::Cancelled]),
            default              => false, // Final states tidak bisa berubah
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Menunggu Pembayaran QRIS',
            self::WaitingPayment => 'Menunggu Bayar di Kasir',
            self::Paid           => 'Sudah Dibayar',
            self::Processing     => 'Sedang Diproses',
            self::Ready          => 'Siap Diambil',
            self::Completed      => 'Selesai',
            self::Cancelled      => 'Dibatalkan',
            self::Expired        => 'Kadaluarsa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'yellow',
            self::WaitingPayment => 'orange',
            self::Paid           => 'blue',
            self::Processing     => 'purple',
            self::Ready          => 'cyan',
            self::Completed      => 'green',
            self::Cancelled      => 'red',
            self::Expired        => 'gray',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::PendingPayment => 'qrcode',
            self::WaitingPayment => 'cash',
            self::Paid           => 'check-circle',
            self::Processing     => 'fire',
            self::Ready          => 'bell',
            self::Completed      => 'badge-check',
            self::Cancelled      => 'x-circle',
            self::Expired        => 'clock',
        };
    }
}
