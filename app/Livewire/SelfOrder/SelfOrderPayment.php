<?php

namespace App\Livewire\SelfOrder;

use App\Models\SelfOrder;
use App\Services\MidtransService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Pembayaran QRIS')]
class SelfOrderPayment extends Component
{
    #[Locked]
    public string $token;

    public ?SelfOrder $selfOrder = null;
    public bool $isExpired = false;
    public bool $isPaid    = false;
    public bool $isChecking = false;
    public string $flashMessage = '';

    public function mount(string $token): void
    {
        $this->token    = $token;
        $selfOrder = SelfOrder::findByToken($token);

        if (!$selfOrder) {
            abort(404, 'Order tidak ditemukan.');
        }

        // Jika bukan QRIS, redirect ke tracking
        if ($selfOrder->payment_method !== 'qris') {
            $this->redirect(route('self-order.tracking', ['token' => $token]), navigate: true);
            return;
        }

        $this->selfOrder = $selfOrder;

        // Anggap kadaluarsa juga kalau waktu sudah lewat expired_at walau status DB belum sempat
        // di-update job/webhook (jeda hingga ~1 menit) — jangan tampilkan QR yang sudah tidak valid.
        $expiredAt      = $selfOrder->paymentTransaction?->expired_at;
        $timeHasExpired = $expiredAt && now()->greaterThan($expiredAt);

        $this->isExpired = $selfOrder->isExpired() || $selfOrder->isCancelled() || $timeHasExpired;
        $this->isPaid    = $selfOrder->isPaid() || $selfOrder->isCompleted() || $selfOrder->isProcessing() || $selfOrder->isReady();

        // Jika sudah dibayar, redirect ke tracking
        if ($this->isPaid) {
            $this->redirect(route('self-order.tracking', ['token' => $token]), navigate: true);
        }
    }

    #[Computed]
    public function qrCodeUrl(): ?string
    {
        return $this->selfOrder?->paymentTransaction?->qr_code_url;
    }

    #[Computed]
    public function expiresAt(): ?string
    {
        $expiredAt = $this->selfOrder?->paymentTransaction?->expired_at;
        return $expiredAt?->toIso8601String();
    }

    /**
     * Waktu kadaluarsa QRIS dalam epoch milliseconds — dipakai countdown client-side (Alpine)
     * yang menghitung sisa waktu dari target absolut (bukan dekrement lokal), sehingga tidak
     * pernah "reset ke awal" walau Livewire me-render ulang komponen ini (klik Cek Status, dll),
     * karena nilainya TETAP sama di setiap request selama QRIS ini belum expired.
     */
    #[Computed]
    public function expiresAtEpochMs(): int
    {
        $expiredAt = $this->selfOrder?->paymentTransaction?->expired_at;
        return $expiredAt ? $expiredAt->valueOf() : 0;
    }

    #[Computed]
    public function expiresInSeconds(): int
    {
        $expiredAt = $this->selfOrder?->paymentTransaction?->expired_at;
        if (!$expiredAt) return 0;
        return max(0, now()->diffInSeconds($expiredAt, false));
    }

    /**
     * Durasi TOTAL QRIS berlaku (dari saat dibuat sampai kadaluarsa), dalam detik.
     * Berbeda dari expiresInSeconds() yang berubah tiap request (sisa waktu SEKARANG) —
     * nilai ini TETAP/stabil di sepanjang siklus hidup QRIS ini, jadi aman dipakai sebagai
     * penyebut tetap untuk progress bar (dan tidak memicu reset countdown client-side saat
     * Livewire re-render, karena angkanya tidak berubah-ubah antar request).
     */
    #[Computed]
    public function totalExpirySeconds(): int
    {
        $paymentTx = $this->selfOrder?->paymentTransaction;
        if (!$paymentTx?->expired_at || !$paymentTx?->created_at) {
            return 0;
        }
        return max(1, $paymentTx->created_at->diffInSeconds($paymentTx->expired_at));
    }

    /**
     * Tombol "Cek Status Pembayaran" — fallback jika SSE tidak berfungsi.
     * Throttled di route level (10/menit per IP).
     */
    public function checkPaymentStatus(MidtransService $midtransService): void
    {
        if ($this->isChecking) return;
        $this->isChecking = true;

        try {
            $this->selfOrder->refresh();

            if ($this->selfOrder->isPaid() || $this->selfOrder->isProcessing() || $this->selfOrder->isCompleted()) {
                $this->redirect(route('self-order.tracking', ['token' => $this->token]), navigate: true);
                return;
            }

            if ($this->selfOrder->isExpired()) {
                $this->isExpired    = true;
                $this->flashMessage = 'QR Code sudah kadaluarsa. Silakan lakukan pesanan baru.';
                return;
            }

            // Safety net: waktu sudah lewat expired_at walau status DB belum sempat di-update
            // oleh job scheduler/webhook (ada jeda hingga ~1 menit). Jangan biarkan customer
            // tetap bisa mencoba bayar QRIS yang sudah lewat waktu berdasarkan jam server.
            $expiredAt = $this->selfOrder->paymentTransaction?->expired_at;
            if ($expiredAt && now()->greaterThan($expiredAt)) {
                $this->isExpired    = true;
                $this->flashMessage = 'QR Code sudah kadaluarsa. Silakan lakukan pesanan baru.';
                return;
            }

            // Cek ke Midtrans API
            if ($this->selfOrder->paymentTransaction) {
                try {
                    $midtransStatus = $midtransService->checkStatus(
                        $this->selfOrder->paymentTransaction->midtrans_order_id
                    );

                    $txStatus = $midtransStatus['transaction_status'] ?? '';

                    if (in_array($txStatus, ['settlement', 'capture'])) {
                        $webhookPayload = \App\DTOs\Payment\MidtransWebhookPayload::fromArray($midtransStatus);
                        app(\App\Actions\SelfOrder\HandleSelfOrderWebhookAction::class)->execute($webhookPayload);

                        $this->flashMessage = 'Pembayaran berhasil dikonfirmasi!';
                        $this->redirect(route('self-order.tracking', ['token' => $this->token]), navigate: true);
                        return;
                    } elseif (in_array($txStatus, ['deny', 'cancel', 'expire'])) {
                        $this->isExpired    = true;
                        $this->flashMessage = 'Pembayaran dibatalkan atau kadaluarsa.';
                        return;
                    } else {
                        $this->flashMessage = 'Pembayaran belum diterima. Pastikan Anda sudah scan QR Code.';
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('checkPaymentStatus error: ' . $e->getMessage());
                    $this->flashMessage = 'Layanan cek pembayaran tidak tersedia. Coba beberapa saat lagi.';
                }
            }
        } finally {
            $this->isChecking = false;
        }
    }

    public function render()
    {
        return view('livewire.self-order.self-order-payment')
            ->layout('layouts.self-order');
    }
}
