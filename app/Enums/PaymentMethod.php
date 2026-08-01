<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Qris = 'qris';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Qris => 'QRIS',
        };
    }

    public function requiresGateway(): bool
    {
        return $this === self::Qris;
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknotes',
            self::Qris => 'qr-code',
        };
    }
}
