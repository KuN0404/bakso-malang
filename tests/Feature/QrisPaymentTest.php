<?php

namespace Tests\Feature;

use App\Actions\Payment\HandleMidtransWebhookAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\Category;
use App\Models\Component;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\Transaction;
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

    // -----------------------------------------------------------------
    // POS QRIS: settlement terlambat / oversold — stok tidak boleh minus,
    // dan transaksi yang sudah dibayar TIDAK BOLEH hilang (rollback).
    // -----------------------------------------------------------------

    private function cacheQrisCart(string $orderId, array $cart, float $total): void
    {
        Cache::put("qris_cart_{$orderId}", [
            'cart'            => $cart,
            'subtotal'        => $total,
            'tax_amount'      => 0,
            'total'           => $total,
            'customer_name'   => 'Budi',
            'order_type'      => 'take_away',
            'service_area_id' => null,
            'notes'           => null,
        ], now()->addMinutes(30));
    }

    public function test_pos_qris_settlement_deducts_only_available_stock_when_oversold_direct_product(): void
    {
        $user     = User::factory()->create(['username' => 'qris-oversold-1']);
        $category = Category::create(['name' => 'Menu', 'slug' => 'menu-qris-1', 'sort_order' => 1, 'is_active' => true]);
        $product  = Product::create([
            'category_id' => $category->id, 'name' => 'Bakso Oversold', 'slug' => 'bakso-oversold-1',
            'sku' => 'SKU-OVR-1', 'price' => 15000, 'is_active' => true, 'track_stock' => true, 'stock' => 1,
        ]);

        $paymentTx = PaymentTransaction::create([
            'invoice_number' => 'INV-QRIS-OVR-1', 'midtrans_order_id' => 'ORDER-QRIS-OVR-1',
            'payment_method' => 'qris', 'amount' => 30000,
            'status' => PaymentTransactionStatus::Pending->value, 'created_by' => $user->id,
        ]);

        // Pesanan butuh 3, tapi stok yang tersisa (setelah diambil transaksi lain) tinggal 1.
        $this->cacheQrisCart('ORDER-QRIS-OVR-1', [[
            'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => 15000, 'quantity' => 3, 'modifier_total' => 0, 'subtotal' => 45000,
        ]], 45000);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id' => 'ORDER-QRIS-OVR-1', 'transaction_id' => 'MID-TX-OVR-1',
            'transaction_status' => 'settlement', 'payment_type' => 'qris',
            'gross_amount' => '45000.00', 'status_code' => '200',
        ]);

        $transaction = app(HandleMidtransWebhookAction::class)->execute($payload);

        // Transaksi yang sudah dibayar TETAP tercatat, bukan hilang karena rollback.
        $this->assertNotNull($transaction);
        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(PaymentTransactionStatus::Paid, $paymentTx->fresh()->status);

        // Stok floor di 0, tidak pernah minus.
        $this->assertEquals(0, $product->fresh()->stock);
    }

    public function test_pos_qris_settlement_bom_shortage_keeps_transaction_instead_of_rollback(): void
    {
        $user      = User::factory()->create(['username' => 'qris-oversold-2']);
        $category  = Category::create(['name' => 'Menu', 'slug' => 'menu-qris-2', 'sort_order' => 1, 'is_active' => true]);
        $component = Component::create(['code' => 'CMP-OVR-1', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 2]);
        $product   = Product::create([
            'category_id' => $category->id, 'name' => 'Bakso BOM Oversold', 'slug' => 'bakso-bom-oversold-1',
            'sku' => 'SKU-OVR-2', 'price' => 15000, 'is_active' => true, 'track_stock' => false,
        ]);
        ProductBom::create(['product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3]);

        $paymentTx = PaymentTransaction::create([
            'invoice_number' => 'INV-QRIS-OVR-2', 'midtrans_order_id' => 'ORDER-QRIS-OVR-2',
            'payment_method' => 'qris', 'amount' => 15000,
            'status' => PaymentTransactionStatus::Pending->value, 'created_by' => $user->id,
        ]);

        // Butuh 1 produk x 3 komponen/produk = 3 komponen, tapi stok komponen tinggal 2.
        $this->cacheQrisCart('ORDER-QRIS-OVR-2', [[
            'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => 15000, 'quantity' => 1, 'modifier_total' => 0, 'subtotal' => 15000,
        ]], 15000);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id' => 'ORDER-QRIS-OVR-2', 'transaction_id' => 'MID-TX-OVR-2',
            'transaction_status' => 'settlement', 'payment_type' => 'qris',
            'gross_amount' => '15000.00', 'status_code' => '200',
        ]);

        // Sebelum fix: ini akan throw InsufficientStockException dan me-rollback SELURUH
        // pencatatan pembayaran (transaksi hilang meski uang sudah masuk). Sekarang harus tetap
        // berhasil membuat Transaction, hanya stok komponen yang floor di 0.
        $transaction = app(HandleMidtransWebhookAction::class)->execute($payload);

        $this->assertNotNull($transaction);
        $this->assertEquals(1, Transaction::count());
        $this->assertEquals(PaymentTransactionStatus::Paid, $paymentTx->fresh()->status);
        $this->assertEquals(0, (float) $component->fresh()->stock);
    }
}
