<?php
namespace Tests\Feature;

use App\Models\Category;
use App\Models\PaymentSource;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrisUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_qris_modal_renders_without_blade_error(): void
    {
        $user = User::factory()->create(['username' => 'qris-ui-1']);
        Livewire::actingAs($user)->test(\App\Livewire\PosCheckout::class)
            ->set('showQrisModal', true)
            ->set('qrisCodeUrl', 'https://example.test/qr.png')
            ->set('qrisInvoiceNumber', 'INV-UI-1')
            ->set('qrisExpiresIn', 120)
            ->assertStatus(200)
            ->assertSee('Pembayaran QRIS')
            ->assertSee('Cek Status Pembayaran');
    }

    public function test_customer_display_renders_without_blade_error(): void
    {
        $user = User::factory()->create(['username' => 'qris-ui-2']);
        Livewire::actingAs($user)->test(\App\Livewire\CustomerDisplay::class)
            ->assertStatus(200);
    }
}
