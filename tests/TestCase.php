<?php

namespace Tests;

use App\Models\Setting;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * `php artisan test` menjalankan seluruh test case dalam SATU proses PHP.
     * Setting::$memo (lihat catatan di app/Models/Setting.php) tidak reset
     * sendiri antar test class — kalau test class A membaca sebuah setting
     * sebelum diisi (dapat default) dan memoize-nya, test class B yang
     * mengisi setting itu lewat DB/fixture langsung (bukan Setting::set())
     * bisa tetap dapat nilai basi dari memo test A. Reset di sini (bukan di
     * satu test file tertentu) supaya SEMUA test mulai bersih, terlepas dari
     * urutan/isolasi menjalankannya.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Setting::forgetMemo();
    }
}
