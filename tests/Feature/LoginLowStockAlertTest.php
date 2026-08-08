<?php

namespace Tests\Feature;

use App\Livewire\Admin\Dashboard;
use App\Models\Component;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class LoginLowStockAlertTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Dummy Cloudflare Turnstile test keys sudah di-set di .env, jadi verifyTurnstile()
        // tetap memanggil endpoint siteverify — mock supaya selalu sukses tanpa hit jaringan asli.
        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true], 200),
        ]);
    }

    private function makeUser(string $username = 'login_test_user'): User
    {
        return User::factory()->create([
            'username' => $username,
            'password' => bcrypt('password123'),
        ]);
    }

    private function login(string $username): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', [
            'username'               => $username,
            'password'               => 'password123',
            'cf-turnstile-response'  => 'dummy-token',
        ]);
    }

    public function test_login_flashes_low_stock_alert_when_items_are_low(): void
    {
        $user = $this->makeUser();

        Ingredient::create([
            'code' => 'ING-LOW-1', 'name' => 'Bahan Menipis', 'unit' => 'kg',
            'stock' => 1, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);

        $response = $this->login($user->username);

        $response->assertRedirect('/admin');
        $this->assertTrue(session()->has('low_stock_alert_count'));
        $this->assertGreaterThan(0, session('low_stock_alert_count'));
    }

    public function test_login_does_not_flash_alert_when_all_stock_ok(): void
    {
        $user = $this->makeUser();

        Ingredient::create([
            'code' => 'ING-OK-1', 'name' => 'Bahan Aman', 'unit' => 'kg',
            'stock' => 100, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);
        Component::create([
            'code' => 'COMP-OK-1', 'name' => 'Komponen Aman', 'unit' => 'pcs',
            'stock' => 100, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);

        $response = $this->login($user->username);

        $response->assertRedirect('/admin');
        $this->assertFalse(session()->has('low_stock_alert_count'));
    }

    public function test_dashboard_dispatches_notify_toast_from_flashed_session_value(): void
    {
        $user = $this->makeUser();

        session()->flash('low_stock_alert_count', 3);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertDispatched('notify', type: 'warning');
    }
}
