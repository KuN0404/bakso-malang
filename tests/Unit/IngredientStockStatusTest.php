<?php

namespace Tests\Unit;

use App\Models\Ingredient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientStockStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeIngredient(float $stock, float $minimumStock = 10): Ingredient
    {
        return Ingredient::create([
            'code'          => 'ING-' . uniqid(),
            'name'          => 'Bahan Uji',
            'unit'          => 'kg',
            'stock'         => $stock,
            'minimum_stock' => $minimumStock,
            'cost_price'    => 1000,
            'is_active'     => true,
        ]);
    }

    public function test_is_low_stock_true_when_stock_at_or_below_minimum_and_above_zero(): void
    {
        $atMinimum = $this->makeIngredient(stock: 10, minimumStock: 10);
        $belowMinimum = $this->makeIngredient(stock: 5, minimumStock: 10);

        $this->assertTrue($atMinimum->isLowStock());
        $this->assertTrue($belowMinimum->isLowStock());
    }

    public function test_is_low_stock_false_when_stock_zero(): void
    {
        $outOfStock = $this->makeIngredient(stock: 0, minimumStock: 10);

        $this->assertFalse($outOfStock->isLowStock());
    }

    public function test_is_low_stock_false_when_stock_above_minimum(): void
    {
        $healthy = $this->makeIngredient(stock: 50, minimumStock: 10);

        $this->assertFalse($healthy->isLowStock());
    }

    public function test_is_out_of_stock_true_when_stock_zero_or_negative(): void
    {
        $zero = $this->makeIngredient(stock: 0, minimumStock: 10);

        $this->assertTrue($zero->isOutOfStock());
    }

    public function test_is_out_of_stock_false_when_stock_positive(): void
    {
        $positive = $this->makeIngredient(stock: 1, minimumStock: 10);

        $this->assertFalse($positive->isOutOfStock());
    }

    public function test_get_stock_status_returns_ok_low_out_correctly(): void
    {
        $ok  = $this->makeIngredient(stock: 50, minimumStock: 10);
        $low = $this->makeIngredient(stock: 5, minimumStock: 10);
        $out = $this->makeIngredient(stock: 0, minimumStock: 10);

        $this->assertEquals('ok', $ok->getStockStatus());
        $this->assertEquals('low', $low->getStockStatus());
        $this->assertEquals('out', $out->getStockStatus());
    }

    public function test_scope_low_stock_excludes_items_above_minimum(): void
    {
        $this->makeIngredient(stock: 50, minimumStock: 10); // sehat, tidak ikut
        $low = $this->makeIngredient(stock: 5, minimumStock: 10);
        $out = $this->makeIngredient(stock: 0, minimumStock: 10);

        $ids = Ingredient::lowStock()->pluck('id')->sort()->values()->toArray();

        $this->assertEqualsCanonicalizing([$low->id, $out->id], $ids);
    }

    public function test_scope_out_of_stock_matches_is_out_of_stock_semantics(): void
    {
        $this->makeIngredient(stock: 5, minimumStock: 10); // menipis tapi tidak habis
        $out = $this->makeIngredient(stock: 0, minimumStock: 10);

        $ids = Ingredient::outOfStock()->pluck('id')->toArray();

        $this->assertEquals([$out->id], $ids);
    }
}
