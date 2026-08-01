<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Midtrans Merchant Credentials
    |--------------------------------------------------------------------------
    */
    'server_key'   => env('MIDTRANS_SERVER_KEY', ''),
    'client_key'   => env('MIDTRANS_CLIENT_KEY', ''),
    'merchant_id'  => env('MIDTRANS_MERCHANT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    | Set to true for Midtrans Production, false for Sandbox.
    | Set curl_verify to false in local development to bypass SSL cURL issues.
    */
    'is_production' => env('MIDTRANS_IS_PRODUCTION', false),
    'curl_verify'   => env('MIDTRANS_CURL_VERIFY', env('APP_ENV') !== 'local'),

    /*
    |--------------------------------------------------------------------------
    | 3DS & Sanitize
    |--------------------------------------------------------------------------
    */
    'is_sanitized' => true,
    'is_3ds'       => true,

    /*
    |--------------------------------------------------------------------------
    | QRIS Expiry
    |--------------------------------------------------------------------------
    | Default: 5 minutes. Configurable via Admin Settings.
    | Min: 3 menit | Max: 15 menit
    */
    'qris_expiry_minutes_default' => 5,
    'qris_expiry_minutes_min'     => 3,
    'qris_expiry_minutes_max'     => 15,
];
