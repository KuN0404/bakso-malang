<?php

namespace Tests\Feature;

use App\Actions\SelfOrder\AcceptSelfOrderPaymentAction;
use App\Actions\SelfOrder\ClaimSelfOrderAction;
use App\Actions\SelfOrder\HandleSelfOrderWebhookAction;
use App\Actions\SelfOrder\PlaceSelfOrderAction;
use App\DTOs\Payment\MidtransWebhookPayload;
use App\Enums\PaymentTransactionStatus;
use App\Enums\SelfOrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Jobs\SelfOrder\CheckExpiredSelfOrderReservationsJob;
use App\Models\Category;
use App\Models\Component;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\SelfOrder;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\StockReservation;
use App\Models\User;
use App\Services\MidtransService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SelfOrderTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name'       => 'Menu Utama',
            'slug'       => 'menu-utama',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        Setting::create([
            'group' => 'general',
            'key'   => 'tax_percentage',
            'value' => '10',
            'type'  => 'integer',
        ]);

        PaymentSource::create([
            'name'       => 'Cash',
            'type'       => 'cash',
            'is_active'  => true,
            'sort_order' => 1,
        ]);
    }

    private function makeProduct(array $overrides = []): Product
    {
        static $i = 0;
        $i++;

        return Product::create(array_merge([
            'category_id'  => $this->category->id,
            'name'         => "Bakso Test {$i}",
            'slug'         => "bakso-test-{$i}",
            'sku'          => "SKU-{$i}",
            'price'        => 10000,
            'is_active'    => true,
            'track_stock'  => true,
            'stock'        => 10,
        ], $overrides));
    }

    private function makeCashier(): User
    {
        static $i = 0;
        $i++;

        return User::factory()->create(['username' => "cashier{$i}"]);
    }

    private function openShiftFor(User $user): Shift
    {
        return Shift::create([
            'user_id'      => $user->id,
            'started_at'   => now(),
            'opening_cash' => 100000,
            'status'       => 'open',
        ]);
    }

    private function validItem(Product $product, int $qty = 1): array
    {
        return [
            'product_id' => $product->id,
            'quantity'   => $qty,
            'modifiers'  => [],
        ];
    }

    private function validCheckoutData(Product $product, array $overrides = []): array
    {
        return array_merge([
            'customer_name'   => 'Budi Santoso',
            'customer_phone'  => '081234567890',
            'customer_email'  => null,
            'payment_method'  => 'cash_on_counter',
            'order_type'      => 'dine_in',
            'notes'           => null,
            'items'           => [$this->validItem($product)],
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
        ], $overrides);
    }

    // -----------------------------------------------------------------
    // PlaceSelfOrderAction
    // -----------------------------------------------------------------

    public function test_place_order_calculates_price_from_database_not_client(): void
    {
        $product = $this->makeProduct(['price' => 15000]);

        $action = app(PlaceSelfOrderAction::class);
        $selfOrder = $action->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 2)]]),
            '127.0.0.1'
        );

        // subtotal = 15000 * 2 = 30000, tax 10% = 3000, total = 33000
        $this->assertEquals(30000, (float) $selfOrder->subtotal);
        $this->assertEquals(3000, (float) $selfOrder->tax_amount);
        $this->assertEquals(33000, (float) $selfOrder->total);
        $this->assertEquals(SelfOrderStatus::WaitingPayment, $selfOrder->status);
        $this->assertCount(1, $selfOrder->items);
    }

    public function test_place_order_rejects_inactive_product(): void
    {
        $product = $this->makeProduct(['is_active' => false]);

        $this->expectException(\RuntimeException::class);

        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );
    }

    public function test_place_order_throws_when_stock_insufficient(): void
    {
        $product = $this->makeProduct(['stock' => 1]);

        $this->expectException(InsufficientStockException::class);

        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 5)]]),
            '127.0.0.1'
        );
    }

    public function test_place_order_is_idempotent_on_repeated_key(): void
    {
        $product = $this->makeProduct();
        $data    = $this->validCheckoutData($product);

        $action = app(PlaceSelfOrderAction::class);
        $first  = $action->execute($data, '127.0.0.1');
        $second = $action->execute($data, '127.0.0.1');

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, SelfOrder::count());
    }

    public function test_place_order_enforces_max_pending_orders_per_phone(): void
    {
        $product = $this->makeProduct(['stock' => 100]);

        for ($i = 0; $i < 3; $i++) {
            app(PlaceSelfOrderAction::class)->execute(
                $this->validCheckoutData($product, ['idempotency_key' => (string) \Illuminate\Support\Str::uuid()]),
                '127.0.0.1'
            );
        }

        $this->expectException(\RuntimeException::class);

        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['idempotency_key' => (string) \Illuminate\Support\Str::uuid()]),
            '127.0.0.1'
        );
    }

    public function test_place_order_reserves_stock_reducing_available_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 3)]]),
            '127.0.0.1'
        );

        $this->assertEquals(2, $product->fresh()->getAvailableStock());
        // Physical stock is untouched until settlement
        $this->assertEquals(5, $product->fresh()->stock);
    }

    public function test_place_order_with_modifier_then_qris_webhook_settlement_creates_transaction_with_modifier(): void
    {
        // Regression test untuk bug: HandleSelfOrderWebhookAction membaca key modifier yang salah
        // ('name'/'price'/'qty') padahal PlaceSelfOrderAction menyimpan ('modifier_name'/'price_adjustment'/
        // 'quantity'), menyebabkan "Undefined array key 'name'" dan seluruh transaksi di-rollback setiap
        // kali Self Order QRIS dengan modifier di-settle — pesanan tidak pernah masuk ke POS.
        $product = $this->makeProduct(['price' => 10000]);

        $group = ModifierGroup::create([
            'name'            => 'Level Pedas',
            'selection_type'  => 'single',
            'is_required'     => false,
            'min_selections'  => 0,
            'is_active'       => true,
        ]);
        $product->modifierGroups()->attach($group->id);

        $modifier = Modifier::create([
            'modifier_group_id' => $group->id,
            'name'              => 'Extra Pedas',
            'price_adjustment'  => 2000,
            'is_active'         => true,
            'sort_order'        => 1,
        ]);

        // Mock Midtrans agar tidak memanggil API asli
        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('createQrisTransaction')
                ->once()
                ->andReturn([
                    'qr_code_url' => 'https://example.test/qr.png',
                    'expired_at'  => now()->addMinutes(5),
                    'raw'         => ['transaction_status' => 'pending'],
                ]);
        });

        $data = $this->validCheckoutData($product, [
            'payment_method' => 'qris',
            'items' => [[
                'product_id' => $product->id,
                'quantity'   => 2,
                'modifiers'  => [[
                    'modifier_id' => $modifier->id,
                    'quantity'    => 3,
                ]],
            ]],
        ]);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute($data, '127.0.0.1');
        $paymentTx = $selfOrder->paymentTransaction;

        // total = (10000 + 2000*3) x 2 = 32000, +10% pajak (setting tax_percentage=10% dari setUp) = 35200
        $this->assertEquals(35200, (float) $paymentTx->amount);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_id'     => 'MID-TX-MOD-1',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => number_format($paymentTx->amount, 2, '.', ''),
            'status_code'        => '200',
        ]);

        $transaction = app(HandleSelfOrderWebhookAction::class)->execute($payload);

        $this->assertNotNull($transaction);
        $this->assertEquals(SelfOrderStatus::Paid, $selfOrder->fresh()->status);

        $detail = $transaction->details()->first();
        $this->assertNotNull($detail);
        $modifierPivot = $detail->modifiers()->first();
        $this->assertNotNull($modifierPivot);
        $this->assertEquals('Extra Pedas', $modifierPivot->pivot->modifier_name);
        $this->assertEquals(2000, (float) $modifierPivot->pivot->price_adjustment);
        $this->assertEquals(3, $modifierPivot->pivot->quantity);
    }

    public function test_place_order_reserves_bom_components(): void
    {
        $component = Component::create([
            'code'  => 'CMP-1',
            'name'  => 'Bakso Kecil',
            'unit'  => 'pcs',
            'stock' => 20,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        ProductBom::create([
            'product_id'   => $product->id,
            'component_id' => $component->id,
            'quantity'     => 3,
        ]);

        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 2)]]),
            '127.0.0.1'
        );

        // 2 produk x 3 komponen/produk = 6 komponen direservasi
        $this->assertEquals(6, StockReservation::getTotalActiveForComponent($component->id));
        $this->assertEquals(20, $component->fresh()->stock); // fisik belum berkurang
    }

    /**
     * Produk ber-BOM umumnya track_stock=false. Dulu gerbang @if($product->track_stock)
     * di blade membuat badge "Habis" tidak pernah muncul untuk produk seperti ini.
     */
    public function test_bom_product_without_track_stock_reports_unavailable_when_component_empty(): void
    {
        $component = Component::create([
            'code' => 'CMP-AVL', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 0,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        ProductBom::create([
            'product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3,
        ]);

        $product = $product->fresh()->load('bom.component');

        $this->assertSame(0, $product->getAvailableStock());
        $this->assertFalse($product->isAvailable());

        $component->update(['stock' => 9]);
        $product = $product->fresh()->load('bom.component');

        $this->assertSame(3, $product->getAvailableStock()); // 9 / 3
        $this->assertTrue($product->isAvailable());
    }

    /** Keranjang Self Order harus menolak sebelum submit, bukan setelahnya. */
    public function test_self_order_cart_rejects_when_component_insufficient(): void
    {
        $component = Component::create([
            'code' => 'CMP-CART', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 5,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        ProductBom::create([
            'product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3,
        ]);

        // 1 produk butuh 3 (muat), 2 produk butuh 6 (tidak muat dari stok 5).
        Livewire::test(\App\Livewire\SelfOrder\SelfOrderPage::class)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1); // penambahan kedua ditolak

        $this->assertTrue(true);
    }

    /** Dua produk berbeda yang memperebutkan komponen sama harus dijumlahkan. */
    public function test_place_order_rejects_when_two_products_share_scarce_component(): void
    {
        $component = Component::create([
            'code' => 'CMP-SHARE', 'name' => 'Kuah Kaldu', 'unit' => 'porsi', 'stock' => 3,
        ]);

        $a = $this->makeProduct(['track_stock' => false]);
        $b = $this->makeProduct(['track_stock' => false]);
        ProductBom::create(['product_id' => $a->id, 'component_id' => $component->id, 'quantity' => 2]);
        ProductBom::create(['product_id' => $b->id, 'component_id' => $component->id, 'quantity' => 2]);

        $this->expectException(InsufficientStockException::class);

        // Masing-masing 1 unit → total butuh 4, stok hanya 3.
        app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($a, ['items' => [$this->validItem($a, 1), $this->validItem($b, 1)]]),
            '127.0.0.1'
        );
    }

    // -----------------------------------------------------------------
    // AcceptSelfOrderPaymentAction (Bayar di Kasir)
    // -----------------------------------------------------------------

    public function test_accept_payment_creates_pos_transaction_and_converts_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        $cashier = $this->makeCashier();
        $this->openShiftFor($cashier);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 2)]]),
            '127.0.0.1'
        );

        $transaction = app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashier->id, 50000);

        $this->assertEquals('completed', $transaction->status);
        $this->assertEquals(SelfOrderStatus::Paid, $selfOrder->fresh()->status);
        $this->assertEquals(3, $product->fresh()->stock); // 5 - 2 dikurangi aktual
        $this->assertEquals(3, $product->fresh()->getAvailableStock());
    }

    /**
     * Self Order memotong stok lewat reservasi (bukan ComponentStockService::deductForBom),
     * sehingga dulu tidak pernah menulis snapshot komposisi komponen. Akibatnya retur
     * menghitung ulang dari BOM yang hidup — salah begitu BOM berubah atau saat oversold.
     */
    public function test_accept_payment_writes_component_snapshot_for_bom_product(): void
    {
        $component = Component::create([
            'code' => 'CMP-SNAP', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 20,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        ProductBom::create([
            'product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3,
        ]);

        $cashier = $this->makeCashier();
        $this->openShiftFor($cashier);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 2)]]),
            '127.0.0.1'
        );

        $transaction = app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashier->id, 50000);

        // 2 produk x 3 = 6 komponen benar-benar terpotong
        $this->assertEquals(14.0, (float) $component->fresh()->stock);

        $detail   = $transaction->details->first();
        $snapshot = $detail->components;

        $this->assertCount(1, $snapshot);
        $this->assertEquals($component->id, $snapshot->first()->component_id);
        $this->assertEquals('bom', $snapshot->first()->source);
        $this->assertEquals(3.0, (float) $snapshot->first()->quantity_per_unit);
        $this->assertEquals(6.0, (float) $snapshot->first()->quantity_total);
    }

    /** Retur transaksi Self Order harus memakai snapshot, bukan hitung ulang dari BOM. */
    public function test_return_of_self_order_uses_snapshot_after_bom_changed(): void
    {
        $component = Component::create([
            'code' => 'CMP-RET', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 20,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        $bomLine = ProductBom::create([
            'product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3,
        ]);

        $cashier = $this->makeCashier();
        $this->openShiftFor($cashier);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product, ['items' => [$this->validItem($product, 1)]]),
            '127.0.0.1'
        );

        $transaction = app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashier->id, 50000);

        $this->assertEquals(17.0, (float) $component->fresh()->stock); // 20 - 3

        // Admin mengubah resep SETELAH penjualan — retur tidak boleh ikut berubah.
        $bomLine->update(['quantity' => 10]);

        app(\App\Services\ComponentStockService::class)
            ->restoreForReturn($transaction->details->first()->id, 1, null, 555);

        // Kembali 3 (sesuai yang dulu dipotong), bukan 10 dari BOM baru.
        $this->assertEquals(20.0, (float) $component->fresh()->stock);
    }

    public function test_accept_payment_rejects_when_status_not_waiting_payment(): void
    {
        $product = $this->makeProduct();
        $cashier = $this->makeCashier();
        $this->openShiftFor($cashier);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );
        $selfOrder->update(['status' => SelfOrderStatus::Cancelled->value]);

        $this->expectException(\DomainException::class);

        app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashier->id, 50000);
    }

    public function test_accept_payment_requires_open_shift(): void
    {
        $product = $this->makeProduct();
        $cashier = $this->makeCashier(); // sengaja tidak buka shift

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('belum membuka shift');

        app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashier->id, 50000);
    }

    public function test_second_cashier_cannot_confirm_order_already_claimed_by_first_cashier(): void
    {
        $product  = $this->makeProduct();
        $cashierA = $this->makeCashier();
        $cashierB = $this->makeCashier();
        $this->openShiftFor($cashierA);
        $this->openShiftFor($cashierB);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );

        app(ClaimSelfOrderAction::class)->execute($selfOrder->id, $cashierA->id);

        $this->expectException(\DomainException::class);

        app(AcceptSelfOrderPaymentAction::class)->execute($selfOrder->id, $cashierB->id, 50000);
    }

    public function test_second_cashier_cannot_claim_already_claimed_order(): void
    {
        $product  = $this->makeProduct();
        $cashierA = $this->makeCashier();
        $cashierB = $this->makeCashier();
        $this->openShiftFor($cashierA);
        $this->openShiftFor($cashierB);

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );

        app(ClaimSelfOrderAction::class)->execute($selfOrder->id, $cashierA->id);

        $this->expectException(\DomainException::class);

        app(ClaimSelfOrderAction::class)->execute($selfOrder->id, $cashierB->id);
    }

    // -----------------------------------------------------------------
    // SelfOrderStatus State Machine
    // -----------------------------------------------------------------

    public function test_self_order_status_transitions_follow_allowed_state_machine(): void
    {
        $this->assertTrue(SelfOrderStatus::WaitingPayment->canTransitionTo(SelfOrderStatus::Paid));
        $this->assertTrue(SelfOrderStatus::Paid->canTransitionTo(SelfOrderStatus::Processing));
        $this->assertTrue(SelfOrderStatus::Processing->canTransitionTo(SelfOrderStatus::Ready));
        $this->assertTrue(SelfOrderStatus::Ready->canTransitionTo(SelfOrderStatus::Completed));

        // Tidak boleh lompat status
        $this->assertFalse(SelfOrderStatus::WaitingPayment->canTransitionTo(SelfOrderStatus::Ready));
        $this->assertFalse(SelfOrderStatus::Completed->canTransitionTo(SelfOrderStatus::Processing));
    }

    public function test_transition_to_throws_on_invalid_status_change(): void
    {
        $product = $this->makeProduct();

        $selfOrder = app(PlaceSelfOrderAction::class)->execute(
            $this->validCheckoutData($product),
            '127.0.0.1'
        );

        $this->expectException(\DomainException::class);

        // WaitingPayment -> Ready langsung tidak diizinkan
        $selfOrder->transitionTo(SelfOrderStatus::Ready);
    }

    // -----------------------------------------------------------------
    // Midtrans Webhook (QRIS) — security & correctness
    // -----------------------------------------------------------------

    private function createQrisOrderWithPaymentTx(Product $product, float $amount = 11000): array
    {
        $selfOrder = SelfOrder::create([
            'queue_number'    => 1,
            'customer_name'   => 'Ani',
            'customer_phone'  => '081200000000',
            'subtotal'        => 10000,
            'tax_amount'      => 1000,
            'total'           => $amount,
            'order_type'      => 'dine_in',
            'payment_method'  => 'qris',
            'status'          => SelfOrderStatus::PendingPayment->value,
            'customer_ip'     => '127.0.0.1',
        ]);

        $selfOrder->items()->create([
            'product_id'   => $product->id,
            'product_name' => $product->name,
            'unit_price'   => $product->price,
            'quantity'     => 1,
            'subtotal'     => $product->price,
        ]);

        $paymentTx = PaymentTransaction::create([
            'self_order_id'     => $selfOrder->id,
            'invoice_number'    => 'SO-TEST-0001',
            'midtrans_order_id' => 'SO-TEST-0001-ABCD',
            'payment_method'    => 'qris',
            'amount'            => $amount,
            'status'            => PaymentTransactionStatus::Pending->value,
            'source'            => 'self_order',
            'expired_at'        => now()->addMinutes(5),
        ]);

        $selfOrder->update(['payment_transaction_id' => $paymentTx->id, 'invoice_number' => $paymentTx->invoice_number]);

        return [$selfOrder->fresh(), $paymentTx->fresh()];
    }

    public function test_midtrans_service_rejects_tampered_gross_amount(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $midtransService = app(MidtransService::class);

        $this->expectException(\InvalidArgumentException::class);

        // Penyerang mengirim gross_amount lebih kecil dari nominal asli
        $midtransService->validateWebhookPayload([
            'order_id'     => $paymentTx->midtrans_order_id,
            'gross_amount' => '1000.00',
        ]);
    }

    public function test_midtrans_service_accepts_matching_gross_amount(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $midtransService = app(MidtransService::class);

        // Tidak melempar exception
        $midtransService->validateWebhookPayload([
            'order_id'     => $paymentTx->midtrans_order_id,
            'gross_amount' => '11000.00',
        ]);

        $this->assertTrue(true);
    }

    public function test_webhook_endpoint_rejects_invalid_signature(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $response = $this->postJson('/api/webhook/midtrans/self-order', [
            'order_id'           => $paymentTx->midtrans_order_id,
            'status_code'        => '200',
            'gross_amount'       => '11000.00',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'signature_key'      => 'invalid-signature',
        ]);

        $response->assertStatus(403);
        $this->assertEquals(PaymentTransactionStatus::Pending, $paymentTx->fresh()->status);
    }

    public function test_webhook_endpoint_rejects_valid_signature_with_tampered_amount(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $orderId     = $paymentTx->midtrans_order_id;
        $statusCode  = '200';
        $grossAmount = '1000.00'; // dipalsukan, aslinya 11000
        $signature   = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));

        $response = $this->postJson('/api/webhook/midtrans/self-order', [
            'order_id'           => $orderId,
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'signature_key'      => $signature,
        ]);

        // Signature valid, tapi amount tidak cocok DB -> harus ditolak (400), bukan diproses
        $response->assertStatus(400);
        $this->assertEquals(PaymentTransactionStatus::Pending, $paymentTx->fresh()->status);
        $this->assertEquals(SelfOrderStatus::PendingPayment, $selfOrder->fresh()->status);
    }

    public function test_webhook_endpoint_processes_valid_settlement_end_to_end(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(['stock' => 5]), 11000);

        $orderId     = $paymentTx->midtrans_order_id;
        $statusCode  = '200';
        $grossAmount = '11000.00';
        $signature   = hash('sha512', $orderId . $statusCode . $grossAmount . config('midtrans.server_key'));

        $response = $this->postJson('/api/webhook/midtrans/self-order', [
            'order_id'           => $orderId,
            'transaction_id'     => 'MID-TX-1',
            'status_code'        => $statusCode,
            'gross_amount'       => $grossAmount,
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'signature_key'      => $signature,
        ]);

        $response->assertStatus(200);
        $this->assertEquals(PaymentTransactionStatus::Paid, $paymentTx->fresh()->status);
        $this->assertEquals(SelfOrderStatus::Paid, $selfOrder->fresh()->status);
        $this->assertNotNull($selfOrder->fresh()->transaction_id);
    }

    public function test_webhook_handler_is_idempotent_on_duplicate_settlement(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_id'     => 'MID-TX-1',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => '11000.00',
            'status_code'        => '200',
        ]);

        $action = app(HandleSelfOrderWebhookAction::class);
        $first  = $action->execute($payload);
        $second = $action->execute($payload);

        $this->assertNotNull($first);
        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, \App\Models\Transaction::count());
    }

    public function test_webhook_handler_expiry_releases_stock_reservation(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($product, 11000);

        StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'product',
            'item_id'         => $product->id,
            'quantity'        => 1,
            'status'          => 'active',
            'expires_at'      => now()->addMinutes(5),
        ]);

        $this->assertEquals(4, $product->fresh()->getAvailableStock());

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_status' => 'expire',
            'payment_type'       => 'qris',
            'gross_amount'       => '11000.00',
            'status_code'        => '200',
        ]);

        app(HandleSelfOrderWebhookAction::class)->execute($payload);

        $this->assertEquals(SelfOrderStatus::Expired, $selfOrder->fresh()->status);
        $this->assertEquals(5, $product->fresh()->getAvailableStock()); // reservation dilepas
    }

    // -----------------------------------------------------------------
    // Settlement Terlambat: QR sudah kadaluarsa (reservasi sudah dilepas job),
    // tapi Midtrans tetap mengonfirmasi pembayaran (customer sempat scan tepat
    // sebelum kadaluarsa, atau webhook telat sampai).
    // -----------------------------------------------------------------

    public function test_late_settlement_after_reservation_released_still_deducts_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($product, 11000);
        // createQrisOrderWithPaymentTx membuat 1 item dengan quantity 1.

        $reservation = StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'product',
            'item_id'         => $product->id,
            'quantity'        => 1,
            'status'          => 'active',
            'expires_at'      => now()->subMinute(), // sudah lewat waktu
        ]);

        // Simulasikan CheckExpiredSelfOrderReservationsJob sudah jalan lebih dulu: reservasi
        // dilepas dan order ditandai Expired — TAPI stok fisik belum pernah dikurangi
        // (karena baru sebatas reservasi, belum ada settlement).
        $reservation->update(['status' => 'released', 'released_at' => now()]);
        $selfOrder->update(['status' => SelfOrderStatus::Expired->value, 'cancelled_reason' => 'QRIS kadaluarsa']);

        $this->assertEquals(5, $product->fresh()->stock); // fisik belum berkurang sama sekali

        // Settlement Midtrans TERLAMBAT datang setelah semua itu.
        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_id'     => 'MID-TX-LATE-1',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => '11000.00',
            'status_code'        => '200',
        ]);

        $transaction = app(HandleSelfOrderWebhookAction::class)->execute($payload);

        $this->assertNotNull($transaction);
        $this->assertEquals(SelfOrderStatus::Paid, $selfOrder->fresh()->status);
        // Stok HARUS tetap dikurangi walau reservasinya sudah tidak ada lagi — ini bug yang
        // diperbaiki: sebelumnya stok tidak pernah tersentuh sama sekali di skenario ini.
        $this->assertEquals(4, $product->fresh()->stock);
    }

    public function test_late_settlement_deducts_only_available_stock_when_oversold_and_logs_critical(): void
    {
        // Stok sudah habis diambil order LAIN sebelum settlement terlambat ini masuk.
        $product = $this->makeProduct(['stock' => 0]);
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($product, 11000);

        $reservation = StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'product',
            'item_id'         => $product->id,
            'quantity'        => 1,
            'status'          => 'released',
            'released_at'     => now(),
            'expires_at'      => now()->subMinute(),
        ]);
        $selfOrder->update(['status' => SelfOrderStatus::Expired->value]);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_id'     => 'MID-TX-LATE-2',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => '11000.00',
            'status_code'        => '200',
        ]);

        // Tidak boleh throw / stok tidak boleh jadi negatif walau kekurangan.
        $transaction = app(HandleSelfOrderWebhookAction::class)->execute($payload);

        $this->assertNotNull($transaction);
        $this->assertEquals(SelfOrderStatus::Paid, $selfOrder->fresh()->status);
        $this->assertEquals(0, $product->fresh()->stock); // floor di 0, bukan -1
    }

    /**
     * Regresi paling penting: settlement terlambat memotong stok komponen secara
     * best-effort (di-clamp di 0). Snapshot HARUS mencatat yang benar-benar terpotong,
     * bukan yang diniatkan — kalau tidak, retur mengembalikan lebih banyak daripada
     * yang pernah keluar dan menciptakan stok hantu.
     */
    public function test_late_settlement_snapshot_records_actual_deducted_components_not_intended(): void
    {
        $component = Component::create([
            'code' => 'CMP-OVS', 'name' => 'Bakso Kecil', 'unit' => 'pcs', 'stock' => 2,
        ]);

        $product = $this->makeProduct(['track_stock' => false]);
        ProductBom::create([
            'product_id' => $product->id, 'component_id' => $component->id, 'quantity' => 3,
        ]);

        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($product, 11000);

        // Reservasi sudah kadung dilepas job expiry → jalur best-effort.
        StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'component',
            'item_id'         => $component->id,
            'quantity'        => 3,
            'status'          => 'released',
            'released_at'     => now(),
            'expires_at'      => now()->subMinute(),
        ]);
        $selfOrder->update(['status' => SelfOrderStatus::Expired->value]);

        $payload = MidtransWebhookPayload::fromArray([
            'order_id'           => $paymentTx->midtrans_order_id,
            'transaction_id'     => 'MID-TX-LATE-BOM',
            'transaction_status' => 'settlement',
            'payment_type'       => 'qris',
            'gross_amount'       => '11000.00',
            'status_code'        => '200',
        ]);

        $transaction = app(HandleSelfOrderWebhookAction::class)->execute($payload);

        // Butuh 3, hanya ada 2 → terpotong 2, floor di 0.
        $this->assertEquals(0.0, (float) $component->fresh()->stock);

        $snapshot = $transaction->details->first()->components->first();
        $this->assertNotNull($snapshot, 'Snapshot komponen wajib ditulis di jalur settlement terlambat');
        $this->assertEquals(3.0, (float) $snapshot->quantity_per_unit, 'Niatnya tetap 3 per unit');
        $this->assertEquals(2.0, (float) $snapshot->quantity_total, 'Yang benar-benar terpotong hanya 2');

        // Retur hanya boleh mengembalikan 2, bukan 3.
        app(\App\Services\ComponentStockService::class)
            ->restoreForReturn($transaction->details->first()->id, 1, null, 777);

        $this->assertEquals(2.0, (float) $component->fresh()->stock);
    }

    // -----------------------------------------------------------------
    // Expired Reservation Cleanup Job
    // -----------------------------------------------------------------

    public function test_expired_reservations_job_marks_cash_orders_expired_and_frees_stock(): void
    {
        $product = $this->makeProduct(['stock' => 5]);

        $selfOrder = SelfOrder::create([
            'queue_number'   => 1,
            'customer_name'  => 'Timeout Customer',
            'customer_phone' => '081211112222',
            'subtotal'       => 10000,
            'tax_amount'     => 1000,
            'total'          => 11000,
            'order_type'     => 'dine_in',
            'payment_method' => 'cash_on_counter',
            'status'         => SelfOrderStatus::WaitingPayment->value,
            'customer_ip'    => '127.0.0.1',
        ]);

        // 'created_at' tidak fillable — set langsung agar order dianggap timeout (31 menit lalu)
        $selfOrder->forceFill([
            'created_at' => now()->subMinutes(31),
            'updated_at' => now()->subMinutes(31),
        ])->save();

        StockReservation::create([
            'reservable_type' => SelfOrder::class,
            'reservable_id'   => $selfOrder->id,
            'item_type'       => 'product',
            'item_id'         => $product->id,
            'quantity'        => 2,
            'status'          => 'active',
            'expires_at'      => now()->addMinutes(5),
        ]);

        $this->assertEquals(3, $product->fresh()->getAvailableStock());

        (new CheckExpiredSelfOrderReservationsJob())->handle(app(\App\Services\StockReservationService::class));

        $this->assertEquals(SelfOrderStatus::Expired, $selfOrder->fresh()->status);
        $this->assertEquals(5, $product->fresh()->getAvailableStock());
    }

    // -----------------------------------------------------------------
    // Input Validation (Livewire form rules)
    // -----------------------------------------------------------------

    public function test_self_order_page_validates_customer_name_and_phone_format(): void
    {
        $product = $this->makeProduct();

        \Livewire\Livewire::test(\App\Livewire\SelfOrder\SelfOrderPage::class)
            ->call('addToCart', $product->id, 1)
            ->set('customerName', '123456') // angka -> tidak valid (regex huruf saja)
            ->set('customerPhone', 'abc')   // bukan nomor valid
            ->call('placeOrder')
            ->assertHasErrors(['customerName', 'customerPhone']);

        $this->assertEquals(0, SelfOrder::count());
    }

    public function test_self_order_page_strips_tags_from_customer_name(): void
    {
        $product = $this->makeProduct();

        \Livewire\Livewire::test(\App\Livewire\SelfOrder\SelfOrderPage::class)
            ->call('addToCart', $product->id, 1)
            ->set('customerName', 'Budi Santoso')
            ->set('customerPhone', '081234567890')
            ->set('paymentMethod', 'cash_on_counter')
            ->set('notes', '<script>alert(1)</script>Extra pedas')
            ->call('placeOrder');

        $selfOrder = SelfOrder::first();
        $this->assertNotNull($selfOrder);
        $this->assertStringNotContainsString('<script>', $selfOrder->notes ?? '');
    }

    // -----------------------------------------------------------------
    // SelfOrderPayment — countdown akurat & auto-expire (bukan lagi decrement lokal)
    // -----------------------------------------------------------------

    public function test_payment_page_total_expiry_seconds_is_stable_full_duration(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);
        // createQrisOrderWithPaymentTx: expired_at = now()->addMinutes(5) -> total durasi ~300 detik
        $paymentTx->forceFill(['created_at' => now()])->save();

        $component = \Livewire\Livewire::test(\App\Livewire\SelfOrder\SelfOrderPayment::class, ['token' => $selfOrder->order_token]);

        $total = $component->instance()->totalExpirySeconds;
        $this->assertGreaterThanOrEqual(295, $total);
        $this->assertLessThanOrEqual(300, $total);

        // Nilai ini HARUS tetap sama walau component di-render ulang (mensimulasikan klik "Cek Status")
        $totalAfterRerender = $component->call('$refresh')->instance()->totalExpirySeconds;
        $this->assertEquals($total, $totalAfterRerender);
    }

    public function test_payment_page_marks_expired_when_time_passed_even_if_db_status_still_pending(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);
        // Simulasikan waktu sudah lewat expired_at, tapi status DB masih 'pending_payment'
        // (job scheduler belum sempat jalan) — halaman harus tetap anggap kadaluarsa.
        $paymentTx->update(['expired_at' => now()->subMinute()]);

        \Livewire\Livewire::test(\App\Livewire\SelfOrder\SelfOrderPayment::class, ['token' => $selfOrder->order_token])
            ->assertSet('isExpired', true);
    }

    public function test_check_payment_status_marks_expired_when_time_passed_before_calling_midtrans(): void
    {
        [$selfOrder, $paymentTx] = $this->createQrisOrderWithPaymentTx($this->makeProduct(), 11000);

        $component = \Livewire\Livewire::test(\App\Livewire\SelfOrder\SelfOrderPayment::class, ['token' => $selfOrder->order_token]);

        // Waktu baru lewat SETELAH halaman dibuka (mensimulasikan customer menunggu sampai habis,
        // lalu klik "Cek Status Manual") — tidak boleh sampai memanggil Midtrans API sama sekali.
        $paymentTx->update(['expired_at' => now()->subSecond()]);

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldNotReceive('checkStatus');
        });

        $component->call('checkPaymentStatus')->assertSet('isExpired', true);
    }
}
