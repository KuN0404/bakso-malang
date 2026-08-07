<?php

namespace App\Jobs;

use App\Services\FonnteService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Kirim satu pesan struk WhatsApp lewat Fonnte. Murni outbound — tidak ada
 * job/route pasangan untuk menerima balasan dari pelanggan.
 */
class SendWhatsappReceiptJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public readonly string $phone,
        public readonly string $message,
    ) {}

    public function handle(FonnteService $fonnte): void
    {
        $sent = $fonnte->sendMessage($this->phone, $this->message);

        if (!$sent) {
            Log::warning('Gagal kirim struk WhatsApp', ['phone' => $this->phone]);
        }
    }
}
