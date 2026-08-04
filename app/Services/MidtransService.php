<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\CoreApi;
use Midtrans\Transaction as MidtransTransaction;
use Illuminate\Support\Facades\Log;

/**
 * MidtransService
 *
 * Wrapper resmi SDK Midtrans untuk pembuatan & pengelolaan transaksi QRIS.
 * Semua panggilan ke Midtrans API harus melalui service ini.
 */
class MidtransService
{
    public function __construct()
    {
        Config::$serverKey    = config('midtrans.server_key');
        Config::$clientKey    = config('midtrans.client_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized  = config('midtrans.is_sanitized');
        Config::$is3ds        = config('midtrans.is_3ds');

        $curlVerify = config('midtrans.curl_verify', true);
        if (! $curlVerify) {
            Config::$curlOptions = [
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_HTTPHEADER     => [],
            ];
        }
    }

    /**
     * Buat transaksi QRIS di Midtrans menggunakan Core API.
     *
     * @param  string  $orderId   Unique Midtrans order ID
     * @param  float   $amount    Total pembayaran
     * @param  string  $customerName
     * @param  int     $expiryMinutes  Durasi QRIS valid (3–15 menit)
     *
     * @return array{qr_code_url: string, expired_at: \Carbon\Carbon, raw: array}
     *
     * @throws \Exception
     */
    public function createQrisTransaction(
        string $orderId,
        float $amount,
        string $customerName,
        int $expiryMinutes
    ): array {
        $expiryMinutes = $this->clampExpiry($expiryMinutes);

        $params = [
            'payment_type' => 'qris',
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) round($amount),
            ],
            'qris' => [
                'acquirer' => 'gopay',
            ],
            'customer_details' => [
                'first_name' => $customerName ?: 'Pelanggan',
            ],
            'custom_expiry' => [
                'expiry_duration' => $expiryMinutes,
                'unit'            => 'minute',
            ],
        ];

        $response = CoreApi::charge($params);
        $raw      = json_decode(json_encode($response), true);

        if (!isset($raw['qr_string']) && !isset($raw['actions'])) {
            Log::error('Midtrans QRIS: response tidak memiliki qr_string', ['raw' => $raw]);
            throw new \RuntimeException('Gagal mendapatkan QR code dari Midtrans.');
        }

        // Ambil QR Code URL dari actions array
        $qrCodeUrl = $this->extractQrCodeUrl($raw);

        return [
            'qr_code_url' => $qrCodeUrl,
            'expired_at'  => now()->addMinutes($expiryMinutes),
            'raw'         => $raw,
        ];
    }

    /**
     * Cek status transaksi Midtrans berdasarkan order ID.
     */
    public function getTransactionStatus(string $orderId): array
    {
        $response = MidtransTransaction::status($orderId);
        return json_decode(json_encode($response), true);
    }

    /**
     * Alias untuk getTransactionStatus.
     */
    public function checkStatus(string $orderId): array
    {
        return $this->getTransactionStatus($orderId);
    }

    /**
     * Batalkan transaksi di Midtrans (best-effort — tidak throw jika gagal).
     */
    public function cancelTransaction(string $orderId): bool
    {
        try {
            MidtransTransaction::cancel($orderId);
            return true;
        } catch (\Exception $e) {
            Log::warning("Midtrans cancel best-effort failed for order [{$orderId}]: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verifikasi signature key dari webhook Midtrans.
     * Formula: SHA512(orderId + statusCode + grossAmount + serverKey)
     */
    public function verifySignature(
        string $orderId,
        string $statusCode,
        string $grossAmount,
        string $incomingSignature
    ): bool {
        $expected = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));
        return hash_equals($expected, $incomingSignature);
    }

    /**
     * Validasi payload webhook terhadap data PaymentTransaction tersimpan di DB.
     * Signature saja tidak cukup — gross_amount pada payload harus dicocokkan
     * dengan nominal yang benar-benar tercatat saat transaksi dibuat, agar
     * payload dengan signature valid tapi amount dipalsukan tetap ditolak.
     *
     * @throws \InvalidArgumentException
     */
    public function validateWebhookPayload(array $data): void
    {
        $orderId     = $data['order_id'] ?? null;
        $grossAmount = $data['gross_amount'] ?? null;

        if (!$orderId || $grossAmount === null) {
            throw new \InvalidArgumentException('Payload webhook tidak lengkap (order_id/gross_amount kosong).');
        }

        $paymentTx = PaymentTransaction::where('midtrans_order_id', $orderId)->first();

        if (!$paymentTx) {
            throw new \InvalidArgumentException("PaymentTransaction untuk order_id [{$orderId}] tidak ditemukan.");
        }

        $expectedAmount = (float) $paymentTx->amount;
        $receivedAmount = (float) $grossAmount;

        if (abs($expectedAmount - $receivedAmount) > 0.01) {
            throw new \InvalidArgumentException(
                "Gross amount tidak cocok untuk order_id [{$orderId}]. " .
                "Expected: {$expectedAmount}, Received: {$receivedAmount}."
            );
        }
    }

    /**
     * Clamp expiry minutes ke batas yang masuk akal.
     */
    private function clampExpiry(int $minutes): int
    {
        $min = config('midtrans.qris_expiry_minutes_min', 3);
        $max = config('midtrans.qris_expiry_minutes_max', 15);
        return max($min, min($max, $minutes));
    }

    /**
     * Ekstrak QR Code URL dari Midtrans response.
     */
    private function extractQrCodeUrl(array $raw): string
    {
        // Core API QRIS response memiliki 'actions' array dengan rel='generate-qr-code'
        if (!empty($raw['actions'])) {
            foreach ($raw['actions'] as $action) {
                if (isset($action['name']) && $action['name'] === 'generate-qr-code') {
                    return $action['url'];
                }
            }
        }

        // Fallback: qr_string langsung (format lama)
        if (!empty($raw['qr_string'])) {
            return 'data:image/png;base64,' . $raw['qr_string'];
        }

        throw new \RuntimeException('Tidak dapat menemukan QR Code URL di respons Midtrans.');
    }
}
