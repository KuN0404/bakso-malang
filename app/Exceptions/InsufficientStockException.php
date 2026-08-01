<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception saat stok komponen tidak mencukupi untuk operasi yang diminta.
 *
 * Dilempar oleh ComponentStockService dan akan mengakibatkan rollback DB transaction.
 */
class InsufficientStockException extends RuntimeException
{
    public function __construct(string $message = 'Stok tidak mencukupi.')
    {
        parent::__construct($message);
    }
}
