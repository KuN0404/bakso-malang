<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal pembersihan data lama (> 3 bulan) berjalan setiap tanggal 1 jam 02:00 dini hari
\Illuminate\Support\Facades\Schedule::command('data:clean')->monthlyOn(1, '02:00');

// Cek QRIS expired setiap menit (safety net jika webhook Midtrans terlambat)
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\Payment\CheckExpiredQrisPaymentJob())->everyMinute();

