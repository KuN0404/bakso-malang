<?php

namespace Tests\Feature;

use App\Actions\Payment\HandleMidtransWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class QrisPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_transaction_state_machine_validates_transitions(): void
    {
        $user = User::factory()->create(['username' => 'testuser1']);

        $paymentTx = PaymentTransaction::create([
            'invoice_number'    => 'INV-TEST-001',
            'midtrans_order_id' => 'ORDER-TEST-001',
            'payment_method'    => 'qris',
            'amount'            => 50000,
            'status'            => PaymentTransactionStatus::Pending->value,
            'created_by'        => $user->id,
        ]);

        $this->assertTrue($paymentTx->status->canTransitionTo(PaymentTransactionStatus::Paid));
        $this->assertTrue($paymentTx->status->canTransitionTo(PaymentTransactionStatus::Expired));
        $this->assertFalse($paymentTx->status->canTransitionTo(PaymentTransactionStatus::Initiated));

        // Test state transition
        $paymentTx->transitionTo(PaymentTransactionStatus::Paid, 'webhook', null, 'Settlement received');

        $this->assertEquals(PaymentTransactionStatus::Paid, $paymentTx->fresh()->status);
        $this->assertCount(1, $paymentTx->statusLogs);
        $this->assertEquals('settlement received', strtolower($paymentTx->statusLogs->first()->note));
    }

    public function test_idempotent_webhook_handling(): void
    {
        $user = User::factory()->create(['username' => 'testuser2']);

        $paymentTx = PaymentTransaction::create([
            'invoice_number'    => 'INV-TEST-002',
            'midtrans_order_id' => 'ORDER-TEST-002',
            'payment_method'    => 'qris',
            'amount'            => 25000,
            'status'            => PaymentTransactionStatus::Paid->value,
            'paid_at'           => now(),
            'created_by'        => $user->id,
        ]);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => 'ORDER-TEST-002',
            'transaction_id'     => 'MIDTRANS-TX-002',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => '25000.00',
            'status_code'        => '200',
        ]);

        $action = app(HandleMidtransWebhookAction::class);
        $result = $action->execute($payload);

        // Harus diabaikan karena sudah status Paid
        $this->assertNull($result);
        $this->assertEquals(PaymentTransactionStatus::Paid, $paymentTx->fresh()->status);
    }
}
