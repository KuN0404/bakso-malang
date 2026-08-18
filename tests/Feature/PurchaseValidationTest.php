<?php

namespace Tests\Feature;

use App\Livewire\Admin\PurchaseCreate;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PurchaseValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeCashierWithPermission(): User
    {
        $user = User::factory()->create(['username' => 'purchase_tester']);
        Permission::firstOrCreate(['name' => 'create_purchases', 'guard_name' => 'web']);
        $user->givePermissionTo('create_purchases');
        return $user;
    }

    private function makeIngredient(): Ingredient
    {
        return Ingredient::create([
            'code'          => 'ING-' . uniqid(),
            'name'          => 'Daging Sapi',
            'unit'          => 'kg',
            'stock'         => 10,
            'minimum_stock' => 2,
            'cost_price'    => 50000,
            'is_active'     => true,
        ]);
    }

    public function test_purchase_save_rejects_nonexistent_ingredient_id(): void
    {
        $user = $this->makeCashierWithPermission();

        Livewire::actingAs($user)
            ->test(PurchaseCreate::class)
            ->set('invoice_number', 'INV-PO-TEST-1')
            ->set('purchase_date', now()->format('Y-m-d'))
            ->set('items', [
                [
                    'item_type'     => 'ingredient',
                    'ingredient_id' => 999999, // tidak ada
                    'product_id'    => '',
                    'quantity'      => 5,
                    'unit_price'    => 10000,
                    'subtotal'      => 50000,
                ],
            ])
            ->call('save')
            ->assertHasErrors(['items.0.ingredient_id' => 'exists']);
    }

    public function test_purchase_save_rejects_nonexistent_product_id(): void
    {
        $user = $this->makeCashierWithPermission();

        Livewire::actingAs($user)
            ->test(PurchaseCreate::class)
            ->set('invoice_number', 'INV-PO-TEST-2')
            ->set('purchase_date', now()->format('Y-m-d'))
            ->set('items', [
                [
                    'item_type'     => 'product',
                    'ingredient_id' => '',
                    'product_id'    => 999999, // tidak ada
                    'quantity'      => 5,
                    'unit_price'    => 10000,
                    'subtotal'      => 50000,
                ],
            ])
            ->call('save')
            ->assertHasErrors(['items.0.product_id' => 'exists']);
    }

    public function test_purchase_save_succeeds_with_valid_items(): void
    {
        $user       = $this->makeCashierWithPermission();
        $ingredient = $this->makeIngredient();

        Livewire::actingAs($user)
            ->test(PurchaseCreate::class)
            ->set('invoice_number', 'INV-PO-TEST-3')
            ->set('purchase_date', now()->format('Y-m-d'))
            ->set('items', [
                [
                    'item_type'     => 'ingredient',
                    'ingredient_id' => $ingredient->id,
                    'product_id'    => '',
                    'quantity'      => 5,
                    'unit_price'    => 10000,
                    'subtotal'      => 50000,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('purchases', ['invoice_number' => 'INV-PO-TEST-3']);
    }
}
