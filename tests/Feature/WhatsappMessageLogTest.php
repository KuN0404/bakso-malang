<?php

namespace Tests\Feature;

use App\Jobs\SendWhatsappReceiptJob;
use App\Models\BlockedPhoneNumber;
use App\Models\WhatsappMessageLog;
use App\Services\PhoneBlacklistService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi untuk: (1) normalisasi nomor HP di modul blacklist (nomor yang sama
 * dalam format beda harus dianggap nomor yang sama), dan (2) SendWhatsappReceiptJob
 * TIDAK BOLEH mengirim WA ke nomor yang sedang diblokir, dan setiap percobaan
 * kirim (terkirim/gagal/diblokir) harus tercatat di WhatsappMessageLog.
 */
class WhatsappMessageLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.fonnte.token' => 'test-fonnte-token']);
    }

    // -----------------------------------------------------------------
    // Normalisasi nomor HP
    // -----------------------------------------------------------------

    public function test_normalize_phone_converts_all_formats_to_canonical_62(): void
    {
        $this->assertSame('6281234567890', BlockedPhoneNumber::normalizePhone('081234567890'));
        $this->assertSame('6281234567890', BlockedPhoneNumber::normalizePhone('6281234567890'));
        $this->assertSame('6281234567890', BlockedPhoneNumber::normalizePhone('+6281234567890'));
        $this->assertSame('6281234567890', BlockedPhoneNumber::normalizePhone('+62 812-3456-7890'));
    }

    public function test_blocking_in_one_format_is_detected_when_checked_in_another_format(): void
    {
        $service = app(PhoneBlacklistService::class);

        $service->block('081234567890', 'Spam');

        $this->assertTrue($service->isBlocked('6281234567890'));
        $this->assertTrue($service->isBlocked('+6281234567890'));
        $this->assertTrue($service->isBlocked('081234567890'));
    }

    public function test_blocking_same_number_twice_in_different_formats_does_not_create_duplicate_rows(): void
    {
        $service = app(PhoneBlacklistService::class);

        $service->block('081234567890', 'Pertama');
        $service->block('6281234567890', 'Kedua (format beda, nomor fisik sama)');

        $this->assertSame(1, BlockedPhoneNumber::count());
    }

    public function test_unblock_works_regardless_of_format_used(): void
    {
        $service = app(PhoneBlacklistService::class);
        $admin = \App\Models\User::factory()->create(['username' => 'wa-admin-1']);

        $service->block('081234567890', 'Spam');
        $this->assertTrue($service->isBlocked('6281234567890'));

        $service->unblock('+6281234567890', $admin->id);

        $this->assertFalse($service->isBlocked('081234567890'));
    }

    // -----------------------------------------------------------------
    // SendWhatsappReceiptJob — guard blacklist + log
    // -----------------------------------------------------------------

    public function test_job_does_not_call_fonnte_and_logs_blocked_when_number_is_blacklisted(): void
    {
        Http::fake();
        app(PhoneBlacklistService::class)->block('081234567890', 'Spam');

        (new SendWhatsappReceiptJob('6281234567890', 'Halo, ini struk Anda', null))
            ->handle(app(\App\Services\FonnteService::class), app(PhoneBlacklistService::class));

        Http::assertNothingSent();

        $log = WhatsappMessageLog::first();
        $this->assertNotNull($log);
        $this->assertSame('blocked', $log->status);
    }

    public function test_job_does_not_call_fonnte_when_number_blocked_in_different_format(): void
    {
        Http::fake();
        // Diblokir dalam format 0..., tapi struk mau dikirim ke nomor tersimpan
        // di transaksi dalam format 62... (nomor fisik sama).
        app(PhoneBlacklistService::class)->block('081234567890', 'Spam');

        (new SendWhatsappReceiptJob('6281234567890', 'Halo', null))
            ->handle(app(\App\Services\FonnteService::class), app(PhoneBlacklistService::class));

        Http::assertNothingSent();
        $this->assertSame('blocked', WhatsappMessageLog::first()->status);
    }

    public function test_job_sends_and_logs_sent_with_fonnte_message_id_when_not_blocked(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response([
                'status' => true,
                'id' => ['80367170'],
                'target' => ['6281234567890'],
            ]),
        ]);

        (new SendWhatsappReceiptJob('6281234567890', 'Halo, ini struk Anda', null))
            ->handle(app(\App\Services\FonnteService::class), app(PhoneBlacklistService::class));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.fonnte.com/send');

        $log = WhatsappMessageLog::first();
        $this->assertSame('sent', $log->status);
        $this->assertSame('80367170', $log->fonnte_message_id);
    }

    public function test_job_logs_failed_when_fonnte_rejects(): void
    {
        Http::fake([
            'api.fonnte.com/send' => Http::response(['status' => false, 'reason' => 'device disconnect']),
        ]);

        (new SendWhatsappReceiptJob('6281234567890', 'Halo', null))
            ->handle(app(\App\Services\FonnteService::class), app(PhoneBlacklistService::class));

        $log = WhatsappMessageLog::first();
        $this->assertSame('failed', $log->status);
        $this->assertSame('device disconnect', $log->reason);
    }

    // -----------------------------------------------------------------
    // Webhook message-status correlation
    // -----------------------------------------------------------------

    public function test_message_status_webhook_updates_log_via_fonnte_message_id(): void
    {
        config(['services.fonnte.webhook_secret' => 'testsecrettoken1234567890abcdef12345678']);

        $log = WhatsappMessageLog::logSent(null, '6281234567890', 'Halo', 'MSG-999');

        $this->postJson('/api/webhook/fonnte/testsecrettoken1234567890abcdef12345678/message-status', [
            'device'  => '6281111111111',
            'id'      => 'MSG-999',
            'stateid' => 'ST-1',
            'status'  => true,
            'state'   => 'delivered',
        ])->assertOk();

        $this->assertSame('delivered', $log->fresh()->fonnte_status);
    }
}
