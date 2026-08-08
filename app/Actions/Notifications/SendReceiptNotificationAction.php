<?php

namespace App\Actions\Notifications;

use App\Jobs\SendWhatsappReceiptJob;
use App\Mail\SelfOrderReceiptMail;
use App\Mail\TransactionReceiptMail;
use App\Models\SelfOrder;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Satu pintu untuk mengirim struk digital (email dan/atau WhatsApp), sesuai
 * kanal yang dipilih admin di Pengaturan → Notifikasi Struk. Dipakai baik
 * dari alur Self Order maupun POS supaya logikanya tidak terduplikasi.
 */
class SendReceiptNotificationAction
{
    /**
     * @param  Transaction  $transaction  Wajib — sumber link struk publik & nomor invoice.
     * @param  SelfOrder|null  $selfOrder  Kalau transaksi berasal dari Self Order,
     *         sertakan (sudah di-load 'items.modifiers') supaya email pakai template
     *         SelfOrderReceiptMail yang lebih lengkap (nomor antrian, status, dst).
     */
    public function send(Transaction $transaction, ?SelfOrder $selfOrder = null): void
    {
        $channel = Setting::get('receipt_channel', 'email_only', 'notification');
        if ($channel === 'none') {
            return;
        }

        $customerName  = $selfOrder->customer_name ?? $transaction->customer_name;
        $customerEmail = $selfOrder->customer_email ?? $transaction->customer_email;
        $customerPhone = $selfOrder->customer_phone ?? $transaction->customer_phone;

        [$sendEmail, $sendWhatsapp] = $this->resolveChannels(
            $channel,
            filled($customerEmail),
            filled($customerPhone)
        );

        if ($sendEmail) {
            $this->sendEmail($transaction, $selfOrder, $customerEmail);
        }

        if ($sendWhatsapp) {
            $this->sendWhatsapp($transaction, $customerName, $customerPhone);
        }
    }

    /**
     * @return array{0: bool, 1: bool} [kirimEmail, kirimWhatsapp]
     */
    private function resolveChannels(string $channel, bool $hasEmail, bool $hasPhone): array
    {
        return match ($channel) {
            'email_only'      => [$hasEmail, false],
            'whatsapp_only'   => [false, $hasPhone],
            'both'            => [$hasEmail, $hasPhone],
            'prefer_email'    => $hasEmail ? [true, false] : [false, $hasPhone],
            'prefer_whatsapp' => $hasPhone ? [false, true] : [$hasEmail, false],
            default           => [false, false],
        };
    }

    private function sendEmail(Transaction $transaction, ?SelfOrder $selfOrder, string $email): void
    {
        try {
            if ($selfOrder) {
                Mail::to($email)->queue(new SelfOrderReceiptMail($selfOrder));
            } else {
                Mail::to($email)->queue(new TransactionReceiptMail($transaction));
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim email struk', ['email' => $email, 'transaction_id' => $transaction->id, 'error' => $e->getMessage()]);
        }
    }

    private function sendWhatsapp(Transaction $transaction, ?string $customerName, string $phone): void
    {
        if (!$transaction->receipt_token) {
            return;
        }

        $template = Setting::get('whatsapp_template', "Halo {nama}, terima kasih sudah berbelanja di {toko}!\n\nStruk pesanan Anda (#{invoice}):\n{link}", 'notification');
        $storeName = Setting::get('store_name', config('app.name', 'Toko'), 'general');

        $message = strtr($template, [
            '{nama}'    => $customerName ?: 'Pelanggan',
            '{invoice}' => $transaction->invoice_number,
            '{toko}'    => $storeName,
            '{link}'    => route('receipt.show', $transaction->receipt_token),
        ]);

        SendWhatsappReceiptJob::dispatch($phone, $message, $transaction->id);
    }
}
