<?php

namespace App\Mail;

use App\Models\SelfOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SelfOrderReceiptMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly SelfOrder $selfOrder,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Struk Pesanan #' . $this->selfOrder->queue_display . ' — ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.self-order-receipt',
        );
    }
}
