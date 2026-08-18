<?php

namespace App\Support;

/**
 * Satu sumber kebenaran untuk normalisasi nomor HP ke format kanonik
 * 62xxxxxxxxxxx (tanpa +/spasi/strip, prefix 0 diganti 62).
 *
 * Nomor HP bisa masuk ke aplikasi ini dalam beberapa bentuk berbeda
 * tergantung siapa/dari mana yang input (customer Self Order: +62/62/0,
 * admin form blokir: bebas, kasir POS: bebas) — tanpa disamakan dulu,
 * exact-string match akan gagal mendeteksi nomor fisik yang sama.
 */
class PhoneNumber
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Ekspresi SQL mentah yang meniru normalize() di atas, dipakai untuk
     * meng-agregasi/mengelompokkan nomor HP langsung di level query
     * (MariaDB, REGEXP_REPLACE tersedia sejak 10.0.5).
     */
    public static function sqlNormalizeExpression(string $column): string
    {
        return "CASE
            WHEN LEFT(REGEXP_REPLACE({$column}, '[^0-9]', ''), 1) = '0'
                THEN CONCAT('62', SUBSTRING(REGEXP_REPLACE({$column}, '[^0-9]', ''), 2))
            ELSE REGEXP_REPLACE({$column}, '[^0-9]', '')
        END";
    }
}
