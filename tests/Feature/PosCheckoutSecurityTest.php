<?php

namespace Tests\Feature;

use App\Actions\Payment\InitiateCashPaymentAction;
use App\Actions\Payment\InitiateQrisPaymentAction;
use App\DTOs\Payment\CartPayload;
use App\Enums\PaymentTransactionStatus;
use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PosCheckoutSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private PaymentSource $cashSource;

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

        $this->cashSource = PaymentSource::create([
            'name'       => 'Cash',
            'type'       => 'cash',
            'is_active'  => true,
            'sort_order' => 1,
        ]);
    }

    private function makeProduct(float $price = 15000): Product
    {
        static $i = 0;
        $i++;

        return Product::create([
            'category_id' => $this->category->id,
            'name'        => "Bakso Test {$i}",
            'slug'        => "bakso-test-{$i}",
            'sku'         => "SKU-{$i}",
            'price'       => $price,
            'is_active'   => true,
            'track_stock' => false,
        ]);
    }

    private function makeCashier(): User
    {
        return User::factory()->create(['username' => 'kasir1']);
    }

    private function tamperedCartPayload(Product $product, float $fakeUnitPrice, string $method = 'cash', ?float $paidAmount = null): CartPayload
    {
        // Simulasi cart yang di-tamper: harga asli produk 15000, tapi client mengirim harga palsu.
        $cart = [
            "{$product->id}_" => [
                'product_id'     => $product->id,
                'product_name'   => $product->name,
                'unit_price'     => $fakeUnitPrice,
                'quantity'       => 2,
                'modifiers'      => [],
                'modifier_total' => 0,
                'subtotal'       => $fakeUnitPrice * 2, // seharusnya 30000, di-tamper jadi kecil
            ],
        ];

        $fakeSubtotal = $fakeUnitPrice * 2;

        return new CartPayload(
            cart:            $cart,
            subtotal:        $fakeSubtotal,
            taxAmount:       0,
            total:           $fakeSubtotal,
            paidAmount:      $paidAmount ?? $fakeSubtotal,
            changeAmount:    0,
            paymentSourceId: $this->cashSource->id,
            paymentMethod:   $method,
            customerName:    'Budi',
            customerPhone:   '',
            customerEmail:   '',
            orderType:       'take_away',
            serviceAreaId:   null,
            pagerId:         null,
            notes:           '',
            idempotencyKey:  (string) \Illuminate\Support\Str::uuid(),
        );
    }

    public function test_cash_payment_recalculates_price_from_database_ignoring_tampered_cart(): void
    {
        $product = $this->makeProduct(15000); // harga asli
        $cashier = $this->makeCashier();

        // Client mengirim unit_price palsu (100) padahal harga asli 15000.
        // paidAmount dibuat cukup besar (100000) agar tidak tersandung validasi kekurangan bayar,
        // supaya assertion di bawah murni menguji apakah harga dihitung ulang dari DB.
        $payload = $this->tamperedCartPayload($product, 100, 'cash', paidAmount: 100000);

        $transaction = app(InitiateCashPaymentAction::class)->execute($payload, $cashier->id);

        // subtotal harus dihitung ulang dari harga DB: 15000 x 2 = 30000, pajak 10% = 3000, total 33000
        $this->assertEquals(30000, (float) $transaction->subtotal);
        $this->assertEquals(3000, (float) $transaction->tax_amount);
        $this->assertEquals(33000, (float) $transaction->total);
        $this->assertEquals(15000, (float) $transaction->details->first()->unit_price);
    }

    public function test_cash_payment_rejects_underpaid_amount_after_recalculation(): void
    {
        $product = $this->makeProduct(15000);
        $cashier = $this->makeCashier();

        // paidAmount di-tamper mengikuti total palsu (200), padahal total asli 33000
        $payload = $this->tamperedCartPayload($product, 100, 'cash');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('kurang dari total');

        app(InitiateCashPaymentAction::class)->execute($payload, $cashier->id);
    }

    public function test_qris_payment_sends_recalculated_total_to_midtrans_and_caches_validated_cart(): void
    {
        $product = $this->makeProduct(15000);
        $cashier = $this->makeCashier();

        $qrisSource = PaymentSource::create([
            'name' => 'QRIS', 'type' => 'qris', 'is_active' => true, 'sort_order' => 2,
        ]);

        $payload = $this->tamperedCartPayload($product, 100, 'qris');
        $payload = new CartPayload(
            cart:            $payload->cart,
            subtotal:        $payload->subtotal,
            taxAmount:       $payload->taxAmount,
            total:           $payload->total,
            paidAmount:      $payload->total,
            changeAmount:    0,
            paymentSourceId: $qrisSource->id,
            paymentMethod:   'qris',
            customerName:    $payload->customerName,
            customerPhone:   $payload->customerPhone,
            customerEmail:   $payload->customerEmail,
            orderType:       $payload->orderType,
            serviceAreaId:   $payload->serviceAreaId,
            pagerId:         $payload->pagerId,
            notes:           $payload->notes,
            idempotencyKey:  $payload->idempotencyKey,
        );

        // Mock MidtransService agar tidak benar-benar memanggil API — sekaligus memverifikasi
        // amount yang dikirim ke Midtrans adalah total hasil validasi ulang server (33000),
        // BUKAN total palsu (200) yang dikirim client.
        $this->mock(\App\Services\MidtransService::class, function ($mock) {
            $mock->shouldReceive('createQrisTransaction')
                ->once()
                ->withArgs(function (string $orderId, float $amount) {
                    return $amount === 33000.0;
                })
                ->andReturn([
                    'qr_code_url' => 'https://example.test/qr.png',
                    'expired_at'  => now()->addMinutes(5),
                    'raw'         => ['transaction_status' => 'pending'],
                ]);
        });

        $paymentTx = app(InitiateQrisPaymentAction::class)->execute($payload, 'INV-TEST-QRIS-1', $cashier->id);

        $this->assertEquals(33000, (float) $paymentTx->amount);

        $cached = Cache::get("qris_cart_{$paymentTx->midtrans_order_id}");
        $this->assertNotNull($cached);
        $this->assertEquals(33000, $cached['total']);
        $this->assertEquals(15000, $cached['cart'][0]['unit_price']);
    }

    /**
     * Regresi: produk ber-BOM dengan track_stock=true dan stock=0 (kondisi normal,
     * karena stok produk BOM memang tidak dipakai — stoknya turunan dari komponen)
     * sebelumnya SELALU ditolak checkout dengan "Stok ... tidak cukup", walau stok
     * komponennya penuh. Penyebabnya validasi di InitiateCashPaymentAction tidak
     * mengecualikan produk ber-BOM.
     */
    public function test_cash_payment_succeeds_for_bom_product_with_zero_product_stock(): void
    {
        $cashier = $this->makeCashier();

        // Tabel units sudah di-seed oleh migrasinya sendiri (Unit::seedDefaults()).
        $unit = \App\Models\Unit::firstWhere('symbol', 'pcs');

        $component = \App\Models\Component::create([
            'code' => 'CMP-001', 'name' => 'Bakso Kecil', 'unit_id' => $unit->id,
            'stock' => 100, 'minimum_stock' => 0, 'cost_price' => 500, 'is_active' => true,
        ]);

        // track_stock=true + stock=0 → justru inilah kombinasi yang dulu bikin gagal.
        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Bakso Urat BOM',
            'slug'        => 'bakso-urat-bom',
            'sku'         => 'SKU-BOM-1',
            'price'       => 15000,
            'is_active'   => true,
            'track_stock' => true,
            'stock'       => 0,
        ]);

        $product->bom()->create(['component_id' => $component->id, 'quantity' => 3]);

        $payload = $this->tamperedCartPayload($product, 15000, 'cash', paidAmount: 100000);

        $transaction = app(InitiateCashPaymentAction::class)->execute($payload, $cashier->id);

        $this->assertEquals(30000, (float) $transaction->subtotal);

        // Stok komponen berkurang 3 x 2 unit = 6; stok produk tidak disentuh.
        $this->assertEquals(94.0, (float) $component->fresh()->stock);
        $this->assertEquals(0, (int) $product->fresh()->stock);
    }
}
