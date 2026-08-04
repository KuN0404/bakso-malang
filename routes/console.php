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

// Cek Self Order expired (QRIS + Cash timeout) setiap menit
\Illuminate\Support\Facades\Schedule::job(new \App\Jobs\SelfOrder\CheckExpiredSelfOrderReservationsJob())->everyMinute();

// Cleanup cart snapshot lama (lebih dari 7 hari) — jalankan setiap hari jam 03:00
\Illuminate\Support\Facades\Schedule::call(function () {
    \App\Models\SelfOrderCartSnapshot::cleanOld(7);
    \Illuminate\Support\Facades\Log::info('Self Order cart snapshots lama berhasil dibersihkan.');
})->dailyAt('03:00');

