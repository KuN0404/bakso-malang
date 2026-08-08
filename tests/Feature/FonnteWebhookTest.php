<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Regresi untuk webhook receiver Fonnte (device-status & message-status).
 * Fonnte tidak punya signature/HMAC sendiri, jadi verifikasi keaslian request
 * dilakukan lewat secret token di URL (VerifyFonnteWebhookSecret) — test ini
 * membuktikan endpoint menolak request tanpa/salah secret, dan memproses
 * dengan benar saat secret cocok.
 */
class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'testsecrettoken1234567890abcdef12345678';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.fonnte.webhook_secret' => self::SECRET]);
    }

    public function test_device_status_webhook_rejects_request_without_secret(): void
    {
        $response = $this->postJson('/api/webhook/fonnte/wrongsecretwrongsecretwrongsecretwrong/device-status', [
            'device' => '628123456789',
            'status' => 'disconnect',
        ]);

        $response->assertStatus(401);
        $this->assertNull(Cache::get('fonnte_device_status'));
    }

    public function test_device_status_webhook_rejects_request_when_secret_not_configured(): void
    {
        config(['services.fonnte.webhook_secret' => null]);

        $response = $this->postJson('/api/webhook/fonnte/' . self::SECRET . '/device-status', [
            'device' => '628123456789',
            'status' => 'connect',
        ]);

        $response->assertStatus(401);
    }

    public function test_device_status_webhook_updates_cache_on_disconnect(): void
    {
        $response = $this->postJson('/api/webhook/fonnte/' . self::SECRET . '/device-status', [
            'device'    => '628123456789',
            'status'    => 'disconnect',
            'timestamp' => now()->timestamp,
            'reason'    => 'logged out from phone',
        ]);

        $response->assertOk();

        $cached = Cache::get('fonnte_device_status');
        $this->assertNotNull($cached);
        $this->assertSame('disconnect', $cached['device_status']);
    }

    public function test_device_status_webhook_updates_cache_on_connect(): void
    {
        Cache::put('fonnte_device_status', ['status' => true, 'device_status' => 'disconnect'], now()->addMinutes(10));

        $response = $this->postJson('/api/webhook/fonnte/' . self::SECRET . '/device-status', [
            'device' => '628123456789',
            'status' => 'connect',
        ]);

        $response->assertOk();
        $this->assertSame('connect', Cache::get('fonnte_device_status')['device_status']);
    }

    public function test_message_status_webhook_accepts_valid_secret(): void
    {
        $response = $this->postJson('/api/webhook/fonnte/' . self::SECRET . '/message-status', [
            'device'  => '628123456789',
            'id'      => 'MSG-123',
            'stateid' => 'ST-1',
            'status'  => true,
            'state'   => 'delivered',
        ]);

        $response->assertOk();
    }

    public function test_message_status_webhook_updates_matching_log_row(): void
    {
        $log = \App\Models\WhatsappMessageLog::logSent(null, '628123456789', 'Halo', 'MSG-123');

        $this->postJson('/api/webhook/fonnte/' . self::SECRET . '/message-status', [
            'device'  => '628123456789',
            'id'      => 'MSG-123',
            'stateid' => 'ST-1',
            'status'  => true,
            'state'   => 'delivered',
        ])->assertOk();

        $this->assertSame('delivered', $log->fresh()->fonnte_status);
    }

    public function test_message_status_webhook_rejects_invalid_secret(): void
    {
        $response = $this->postJson('/api/webhook/fonnte/wrongsecretwrongsecretwrongsecretwrong/message-status', [
            'device' => '628123456789',
            'id'     => 'MSG-123',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_route_rejects_secret_with_invalid_format(): void
    {
        // Secret terlalu pendek / mengandung karakter di luar pola route ({secret} regex)
        // seharusnya tidak match route sama sekali -> 404, bukan 401.
        $response = $this->postJson('/api/webhook/fonnte/short/device-status', [
            'device' => '628123456789',
            'status' => 'connect',
        ]);

        $response->assertStatus(404);
    }
}
