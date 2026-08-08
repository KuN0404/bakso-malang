<?php

namespace Tests\Feature;

use App\Livewire\Admin\Productions;
use App\Models\Component;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ProductionSanityCapTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithPermission(): User
    {
        $user = User::factory()->create(['username' => 'production_tester']);
        Permission::firstOrCreate(['name' => 'create_productions', 'guard_name' => 'web']);
        $user->givePermissionTo('create_productions');
        return $user;
    }

    private function makeIngredient(float $stock = 100, float $costPrice = 1000): Ingredient
    {
        return Ingredient::create([
            'code'          => 'ING-' . uniqid(),
            'name'          => 'Bahan Repacking',
            'unit'          => 'kg',
            'stock'         => $stock,
            'minimum_stock' => 5,
            'cost_price'    => $costPrice,
            'is_active'     => true,
        ]);
    }

    private function makeComponent(float $costPrice = 5000): Component
    {
        return Component::create([
            'code'          => 'COMP-' . uniqid(),
            'name'          => 'Komponen Hasil Repacking',
            'unit'          => 'pcs',
            'stock'         => 0,
            'minimum_stock' => 5,
            'cost_price'    => $costPrice,
            'is_active'     => true,
        ]);
    }

    public function test_production_rejects_output_cost_wildly_exceeding_input_cost(): void
    {
        $user       = $this->makeUserWithPermission();
        $ingredient = $this->makeIngredient(stock: 100, costPrice: 1000); // input murah
        $component  = $this->makeComponent(costPrice: 5000); // HPP komponen saat ini normal

        // Total input cost = 1 kg x 1000 = 1000. Output qty sengaja dibuat sangat kecil (0.001)
        // sehingga HPP hasil = 1000/0.001 = 1.000.000 — 200x lipat dari HPP komponen saat ini (5000).
        Livewire::actingAs($user)
            ->test(Productions::class)
            ->set('production_code', 'PROD-TEST-1')
            ->set('production_date', now()->format('Y-m-d'))
            ->set('inputItems', [
                ['ingredient_id' => $ingredient->id, 'quantity' => 1, 'unit_cost' => 1000, 'subtotal' => 1000],
            ])
            ->set('outputItems', [
                ['component_id' => $component->id, 'quantity' => 0.001, 'unit_cost' => 0, 'subtotal' => 0],
            ])
            ->call('save');

        $this->assertDatabaseMissing('productions', ['production_code' => 'PROD-TEST-1']);
        // Stok bahan baku tidak boleh berkurang karena ditolak sebelum transaksi
        $this->assertEquals(100, $ingredient->fresh()->stock);
    }

    public function test_production_accepts_legitimate_repacking_ratio(): void
    {
        $user       = $this->makeUserWithPermission();
        $ingredient = $this->makeIngredient(stock: 100, costPrice: 1000);
        $component  = $this->makeComponent(costPrice: 1000); // HPP awal dekat dengan hasil repacking wajar

        // 10 kg input x 1000 = 10.000 total cost, dibagi rata ke 10 output pcs → HPP 1000/pcs.
        // Rasio terhadap HPP komponen saat ini (1000) adalah 1:1 — jelas wajar.
        Livewire::actingAs($user)
            ->test(Productions::class)
            ->set('production_code', 'PROD-TEST-2')
            ->set('production_date', now()->format('Y-m-d'))
            ->set('inputItems', [
                ['ingredient_id' => $ingredient->id, 'quantity' => 10, 'unit_cost' => 1000, 'subtotal' => 10000],
            ])
            ->set('outputItems', [
                ['component_id' => $component->id, 'quantity' => 10, 'unit_cost' => 1000, 'subtotal' => 10000],
            ])
            ->call('save');

        $this->assertDatabaseHas('productions', ['production_code' => 'PROD-TEST-2']);
        $this->assertEquals(90, $ingredient->fresh()->stock);
        $this->assertEquals(10, $component->fresh()->stock);
    }

    public function test_production_logs_error_on_transaction_failure(): void
    {
        Log::spy();

        $user       = $this->makeUserWithPermission();
        $ingredient = $this->makeIngredient(stock: 10, costPrice: 1000);
        $component  = $this->makeComponent(costPrice: 1000);

        // Soft-check (unlocked) mengecek TIAP baris input independen terhadap stok saat itu
        // (10), jadi dua baris qty=8 untuk ingredient yang SAMA lolos soft-check individual
        // (10 &gt;= 8). Baru di dalam DB::transaction() baris kedua melihat stok yang sudah
        // dikurangi baris pertama (10-8=2) sehingga 2-8 &lt; 0 memicu throw \Exception yang
        // ditangkap catch block — jalur yang sama seperti kegagalan production nyata lainnya.
        Livewire::actingAs($user)
            ->test(Productions::class)
            ->set('production_code', 'PROD-TEST-3')
            ->set('production_date', now()->format('Y-m-d'))
            ->set('inputItems', [
                ['ingredient_id' => $ingredient->id, 'quantity' => 8, 'unit_cost' => 1000, 'subtotal' => 8000],
                ['ingredient_id' => $ingredient->id, 'quantity' => 8, 'unit_cost' => 1000, 'subtotal' => 8000],
            ])
            ->set('outputItems', [
                ['component_id' => $component->id, 'quantity' => 16, 'unit_cost' => 1000, 'subtotal' => 16000],
            ])
            ->call('save');

        $this->assertDatabaseMissing('productions', ['production_code' => 'PROD-TEST-3']);

        Log::shouldHaveReceived('error')->withArgs(function ($message) {
            return $message === 'Production save failed';
        })->atLeast()->once();
    }
}
