<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Self Order Configuration
    |--------------------------------------------------------------------------
    */

    // Timeout reservation untuk "Bayar di Tempat" (dalam menit)
    // Standar industri quick service: 30 menit
    // Customer harusnya ada di tempat, jadi 30 menit adalah batas yang wajar
    'cash_reservation_timeout_minutes' => env('SELF_ORDER_CASH_TIMEOUT_MINUTES', 30),

    // Maksimum item berbeda dalam 1 Self Order
    // Standar industri POS: 15 item berbeda, cukup untuk konteks restoran cepat saji
    'max_cart_items' => env('SELF_ORDER_MAX_CART_ITEMS', 15),

    // Maksimum quantity per item
    'max_item_quantity' => env('SELF_ORDER_MAX_ITEM_QUANTITY', 20),

    // Maksimum order pending per nomor HP (anti-spam)
    'max_pending_per_phone' => env('SELF_ORDER_MAX_PENDING_PER_PHONE', 3),

    // SSE polling interval (detik) — berapa sering SSE controller cek status dari DB
    'sse_poll_interval_seconds' => env('SELF_ORDER_SSE_POLL_SECONDS', 2),

    // SSE max duration (detik) — maksimum durasi koneksi SSE sebelum client reconnect.
    // Dibatasi rendah agar 1 worker PHP-FPM tidak tertahan lama per koneksi
    // (mitigasi resource exhaustion); browser EventSource otomatis reconnect.
    'sse_max_duration_seconds' => env('SELF_ORDER_SSE_MAX_SECONDS', 120),

    // Stock polling interval di UI (detik) — seberapa sering halaman menu request stok terbaru
    'stock_poll_interval_seconds' => env('SELF_ORDER_STOCK_POLL_SECONDS', 5),

    // Untuk masa depan: PAGER / ServiceArea integration
    // Jika true, customer bisa memilih meja/ServiceArea
    'enable_service_area_selection' => env('SELF_ORDER_ENABLE_SERVICE_AREA', false),
];
