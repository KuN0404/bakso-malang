<?php

namespace App\Enums;

enum KitchenTarget: string
{
    case FOOD = 'food';
    case DRINK = 'drink';
    case NONE = 'none';

    public function label(): string
    {
        return match ($this) {
            self::FOOD => 'Dapur Makanan',
            self::DRINK => 'Bar Minuman',
            self::NONE => 'Tanpa Dapur',
        };
    }

    public static function options(): array
    {
        return [
            self::FOOD->value => self::FOOD->label(),
            self::DRINK->value => self::DRINK->label(),
            self::NONE->value => self::NONE->label(),
        ];
    }

    public static function default(): self
    {
        return self::FOOD;
    }
}
