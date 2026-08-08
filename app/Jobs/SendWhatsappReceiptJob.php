<?php

namespace App\Jobs;

use App\Models\WhatsappMessageLog;
use App\Services\FonnteService;
use App\Services\PhoneBlacklistService;
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
        public readonly ?int $transactionId = null,
    ) {}

    public function handle(FonnteService $fonnte, PhoneBlacklistService $blacklist): void
    {
        // Dicek di sini (titik pengiriman sebenarnya), bukan di action yang
        // dispatch job ini — job di-queue, bisa tertunda, jadi kalau nomor
        // baru diblokir SETELAH job diantre tapi SEBELUM diproses, cek di sini
        // tetap menangkapnya (cek lebih awal tidak akan menangkap race ini).
        if ($blacklist->isBlocked($this->phone)) {
            WhatsappMessageLog::logBlocked($this->transactionId, $this->phone, $this->message);
            Log::info('Kirim struk WhatsApp dibatalkan: nomor diblokir', ['phone' => $this->phone]);
            return;
        }

        $result = $fonnte->sendMessage($this->phone, $this->message);

        if ($result['sent']) {
            WhatsappMessageLog::logSent($this->transactionId, $this->phone, $this->message, $result['id']);
        } else {
            WhatsappMessageLog::logFailed($this->transactionId, $this->phone, $this->message, $result['reason']);
            Log::warning('Gagal kirim struk WhatsApp', ['phone' => $this->phone, 'reason' => $result['reason']]);
        }
    }
}
