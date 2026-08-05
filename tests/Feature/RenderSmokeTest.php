<?php

namespace Tests\Feature;

use App\Enums\SelfOrderStatus;
use App\Models\Category;
use App\Models\PaymentTransaction;
use App\Models\Product;
use App\Models\SelfOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RenderSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_self_order_payment_view_renders_without_error(): void
    {
        $category = Category::create(['name' => 'Menu', 'slug' => 'menu', 'sort_order' => 1, 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Bakso', 'slug' => 'bakso',
            'sku' => 'SKU-1', 'price' => 15000, 'is_active' => true, 'track_stock' => false,
        ]);

        $selfOrder = SelfOrder::create([
            'queue_number' => 1, 'customer_name' => 'Budi', 'customer_phone' => '081234567890',
            'subtotal' => 15000, 'tax_amount' => 0, 'total' => 15000, 'order_type' => 'dine_in',
            'payment_method' => 'qris', 'status' => SelfOrderStatus::PendingPayment->value,
            'customer_ip' => '127.0.0.1',
        ]);
        $selfOrder->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name,
            'unit_price' => $product->price, 'quantity' => 1, 'subtotal' => $product->price,
        ]);

        $paymentTx = PaymentTransaction::create([
            'self_order_id' => $selfOrder->id, 'invoice_number' => 'SO-TEST-1',
            'midtrans_order_id' => 'SO-TEST-1-ABC', 'qr_code_url' => 'https://example.test/qr.png',
            'payment_method' => 'qris', 'amount' => 15000,
            'status' => \App\Enums\PaymentTransactionStatus::Pending->value,
            'source' => 'self_order', 'expired_at' => now()->addMinutes(5),
        ]);
        $selfOrder->update(['payment_transaction_id' => $paymentTx->id]);

        Livewire::test(\App\Livewire\SelfOrder\SelfOrderPayment::class, ['token' => $selfOrder->order_token])
            ->assertStatus(200)
            ->assertSee('Pembayaran QRIS')
            ->assertSee('Cek Status Manual')
            // Regresi: setInterval(tick, ...) (tanpa arrow function) melepas method dari binding
            // `this`-nya, jadi timer countdown terlihat "diam" — reassignment this.remainingMs di
            // dalam tick() gagal diam-diam pada setiap tick setelah yang pertama. Pastikan callback
            // yang di-daftarkan ke setInterval selalu dibungkus arrow function.
            ->assertDontSee('setInterval(tick,', false)
            ->assertSee('setInterval(() => tick(), 1000)', false);
    }

    public function test_self_order_page_view_renders_and_placeorder_button_present(): void
    {
        $category = Category::create(['name' => 'Menu', 'slug' => 'menu2', 'sort_order' => 1, 'is_active' => true]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Bakso 2', 'slug' => 'bakso-2',
            'sku' => 'SKU-2', 'price' => 15000, 'is_active' => true, 'track_stock' => false,
        ]);

        Livewire::test(\App\Livewire\SelfOrder\SelfOrderPage::class)
            ->call('addToCart', Product::first()->id, 1)
            ->call('goToCheckout')
            ->assertStatus(200)
            ->assertSee('Pesan Sekarang');
    }
}
