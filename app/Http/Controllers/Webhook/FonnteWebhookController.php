<?php

namespace App\Http\Controllers\Webhook;

use App\Models\WhatsappMessageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Menerima webhook dari Fonnte (docs.fonnte.com). Middleware VerifyFonnteWebhookSecret
 * memverifikasi request sebelum sampai ke sini (lihat catatan di middleware tersebut —
 * Fonnte tidak punya signature/HMAC sendiri).
 *
 * Fonnte hanya butuh balasan 200 OK; body balasan tidak diparse olehnya.
 */
class FonnteWebhookController
{
    /**
     * Webhook Device Status: dipanggil Fonnte saat device connect/disconnect.
     * Menulis ke cache key 'fonnte_device_status' — key yang sama dipakai
     * Whatsapp::checkWhatsappStatus() (halaman admin WhatsApp) — supaya status
     * langsung ter-update tanpa menunggu polling 30 detik berikutnya.
     */
    public function deviceStatus(Request $request): JsonResponse
    {
        $device = $request->input('device');
        $status = $request->input('status'); // 'connect' | 'disconnect'
        $reason = $request->input('reason');

        Cache::put('fonnte_device_status', [
            'status'        => true,
            'device_status' => $status,
            'device'        => $device,
        ], now()->addMinutes(10));

        if ($status === 'disconnect') {
            Log::warning('Fonnte Webhook: device disconnect', [
                'device' => $device,
                'reason' => $reason,
            ]);
        } else {
            Log::info('Fonnte Webhook: device status update', [
                'device' => $device,
                'status' => $status,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    /**
     * Webhook Update Message Status: dipanggil Fonnte saat status pengiriman pesan
     * berubah (mis. sent/delivered/read/failed). Dikorelasikan ke WhatsappMessageLog
     * lewat fonnte_message_id yang disimpan saat pesan pertama kali dikirim
     * (FonnteService::sendMessage() -> SendWhatsappReceiptJob).
     */
    public function messageStatus(Request $request): JsonResponse
    {
        $id     = $request->input('id');
        $state  = $request->input('state');
        $status = $request->input('status');

        Log::info('Fonnte Webhook: message status update', [
            'device'  => $request->input('device'),
            'id'      => $id,
            'stateid' => $request->input('stateid'),
            'status'  => $status,
            'state'   => $state,
        ]);

        WhatsappMessageLog::updateDeliveryStatusByFonnteId($id, $state ?? $status);

        return response()->json(['message' => 'OK']);
    }
}
