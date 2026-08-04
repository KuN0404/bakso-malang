<?php

namespace App\Exceptions;

/**
 * Dilempar saat kasir mencoba melakukan aksi yang membutuhkan shift terbuka
 * (klaim/proses/bayar pesanan mandiri) padahal belum membuka shift sendiri.
 *
 * Dibuat sebagai subclass \DomainException spesifik (bukan sekadar mencocokkan
 * teks pesan) agar UI bisa mendeteksi kasus ini secara andal — misalnya untuk
 * langsung menawarkan modal "Buka Shift" alih-alih menampilkan error generik.
 */
class NoOpenShiftException extends \DomainException
{
    public function __construct(string $message = 'Anda belum membuka shift. Buka shift terlebih dahulu.')
    {
        parent::__construct($message);
    }
}
