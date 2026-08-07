<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Struk email untuk transaksi POS (tanpa Self Order). Untuk transaksi hasil
 * Self Order, tetap pakai SelfOrderReceiptMail (kontennya lebih lengkap,
 * mis. nomor antrian & status pesanan).
 */
class TransactionReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Transaction $transaction,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Struk Transaksi #' . $this->transaction->invoice_number . ' — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.transaction-receipt',
        );
    }
}
