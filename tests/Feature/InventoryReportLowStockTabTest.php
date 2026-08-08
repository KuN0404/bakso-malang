<?php

namespace Tests\Feature;

use App\Livewire\Admin\InventoryReport;
use App\Models\Component;
use App\Models\Ingredient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryReportLowStockTabTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithPermission(): User
    {
        $user = User::factory()->create(['username' => 'inv_report_tester']);
        Permission::firstOrCreate(['name' => 'view_inventory_reports', 'guard_name' => 'web']);
        $user->givePermissionTo('view_inventory_reports');
        return $user;
    }

    public function test_low_stock_tab_lists_only_low_and_out_of_stock_ingredients_and_components(): void
    {
        $user = $this->makeUserWithPermission();

        $healthyIngredient = Ingredient::create([
            'code' => 'ING-HEALTHY', 'name' => 'Bahan Sehat', 'unit' => 'kg',
            'stock' => 100, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);
        $lowIngredient = Ingredient::create([
            'code' => 'ING-LOW', 'name' => 'Bahan Menipis', 'unit' => 'kg',
            'stock' => 3, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);
        $outIngredient = Ingredient::create([
            'code' => 'ING-OUT', 'name' => 'Bahan Habis', 'unit' => 'kg',
            'stock' => 0, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);

        $healthyComponent = Component::create([
            'code' => 'COMP-HEALTHY', 'name' => 'Komponen Sehat', 'unit' => 'pcs',
            'stock' => 100, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);
        $lowComponent = Component::create([
            'code' => 'COMP-LOW', 'name' => 'Komponen Menipis', 'unit' => 'pcs',
            'stock' => 2, 'minimum_stock' => 10, 'cost_price' => 1000, 'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test(InventoryReport::class)
            ->set('activeTab', 'low_stock');

        $lowStockIngredients = $component->viewData('lowStockIngredients');
        $lowStockComponents  = $component->viewData('lowStockComponents');

        $ingredientIds = $lowStockIngredients->pluck('id')->sort()->values()->toArray();
        $this->assertEqualsCanonicalizing([$lowIngredient->id, $outIngredient->id], $ingredientIds);
        $this->assertNotContains($healthyIngredient->id, $ingredientIds);

        $componentIds = $lowStockComponents->pluck('id')->toArray();
        $this->assertEquals([$lowComponent->id], $componentIds);
        $this->assertNotContains($healthyComponent->id, $componentIds);
    }

    public function test_low_stock_tab_excludes_healthy_stock_items(): void
    {
        $user = $this->makeUserWithPermission();

        Ingredient::create([
            'code' => 'ING-HEALTHY-2', 'name' => 'Bahan Sehat 2', 'unit' => 'kg',
            'stock' => 50, 'minimum_stock' => 5, 'cost_price' => 1000, 'is_active' => true,
        ]);
        Component::create([
            'code' => 'COMP-HEALTHY-2', 'name' => 'Komponen Sehat 2', 'unit' => 'pcs',
            'stock' => 50, 'minimum_stock' => 5, 'cost_price' => 1000, 'is_active' => true,
        ]);

        $component = Livewire::actingAs($user)
            ->test(InventoryReport::class)
            ->set('activeTab', 'low_stock');

        $this->assertCount(0, $component->viewData('lowStockIngredients'));
        $this->assertCount(0, $component->viewData('lowStockComponents'));
    }
}
