<?php

namespace Tests\Feature;

use App\Livewire\PosCheckout;
use App\Models\Category;
use App\Models\Component;
use App\Models\ComponentStockLog;
use App\Models\Modifier;
use App\Models\ModifierGroup;
use App\Models\Product;
use App\Models\ProductBom;
use App\Models\Shift;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PosReturnStockRestorationTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name'       => 'Menu Utama',
            'slug'       => 'menu-utama',
            'sort_order' => 1,
            'is_active'  => true,
        ]);

        $this->cashier = User::factory()->create(['username' => 'kasir_retur']);
    }

    private function makeComponent(string $name, float $stock = 100, float $minimumStock = 10): Component
    {
        static $i = 0;
        $i++;

        return Component::create([
            'code'          => "COMP-{$i}",
            'name'          => $name,
            'unit'          => 'pcs',
            'stock'         => $stock,
            'minimum_stock' => $minimumStock,
            'cost_price'    => 1000,
            'is_active'     => true,
        ]);
    }

    private function makeProduct(float $price = 15000): Product
    {
        static $i = 0;
        $i++;

        return Product::create([
            'category_id' => $this->category->id,
            'name'        => "Produk Test {$i}",
            'slug'        => "produk-test-{$i}",
            'sku'         => "SKU-RET-{$i}",
            'price'       => $price,
            'is_active'   => true,
            'track_stock' => false,
        ]);
    }

    /**
     * Buat transaksi + detail yang sudah "selesai" (completed) seolah sudah dibayar
     * dan stok komponennya sudah dikurangi, siap untuk diretur di test.
     */
    private function makeCompletedTransactionWithDetail(Product $product, int $qty = 2): array
    {
        $shift = Shift::getOrCreateTodayShift($this->cashier->id);

        $transaction = Transaction::create([
            'user_id'         => $this->cashier->id,
            'shift_id'        => $shift->id,
            'invoice_number'  => 'INV-RET-' . uniqid(),
            'queue_number'    => 1,
            'subtotal'        => $product->price * $qty,
            'total'           => $product->price * $qty,
            'paid_amount'     => $product->price * $qty,
            'payment_method'  => 'cash',
            'status'          => 'completed',
            'order_type'      => 'take_away',
            'source'          => 'pos',
        ]);

        $detail = TransactionDetail::create([
            'transaction_id' => $transaction->id,
            'product_id'     => $product->id,
            'product_name'   => $product->name,
            'unit_price'     => $product->price,
            'quantity'       => $qty,
            'subtotal'       => $product->price * $qty,
        ]);

        return [$transaction, $detail];
    }

    private function processReturn(Transaction $transaction, TransactionDetail $detail, int $returnQty): void
    {
        Livewire::actingAs($this->cashier)
            ->test(PosCheckout::class)
            ->set('returnTransaction', $transaction)
            ->set('returnItems', [
                $detail->id => [
                    'selected'     => true,
                    'quantity'     => $returnQty,
                    'max_quantity' => $detail->quantity,
                    'product_id'   => $detail->product_id,
                    'product_name' => $detail->product_name,
                    'unit_price'   => $detail->unit_price,
                    'modifiers'    => [],
                ],
            ])
            ->set('returnReason', 'Test retur otomatis')
            ->call('processReturn');
    }

    public function test_return_restores_bom_component_stock(): void
    {
        $component = $this->makeComponent('Bakso Kecil', stock: 50);
        $product   = $this->makeProduct();

        ProductBom::create([
            'product_id'   => $product->id,
            'component_id' => $component->id,
            'quantity'     => 3, // 3 komponen per 1 produk
        ]);

        // Simulasikan stok sudah dikurangi saat checkout (2 produk terjual x 3 = 6)
        $component->decrement('stock', 6);
        $this->assertEquals(44, $component->fresh()->stock);

        [$transaction, $detail] = $this->makeCompletedTransactionWithDetail($product, qty: 2);

        $this->processReturn($transaction, $detail, returnQty: 2);

        $this->assertEquals(50, $component->fresh()->stock);
        $this->assertDatabaseHas('component_stock_logs', [
            'component_id' => $component->id,
            'type'         => 'return_add',
            'amount'       => 6,
        ]);
    }

    public function test_return_restores_modifier_component_stock(): void
    {
        $component = $this->makeComponent('Sambal Extra', stock: 20);
        $product   = $this->makeProduct();

        $group = ModifierGroup::create([
            'name'            => 'Level Pedas',
            'selection_type'  => 'single',
            'is_required'     => false,
            'is_active'       => true,
        ]);

        $modifier = Modifier::create([
            'modifier_group_id' => $group->id,
            'name'              => 'Sambal Extra',
            'price_adjustment'  => 2000,
            'is_active'         => true,
            'component_id'      => $component->id,
        ]);

        // Simulasikan stok sudah dikurangi saat checkout: 2 produk x 1 modifier qty = 2
        $component->decrement('stock', 2);
        $this->assertEquals(18, $component->fresh()->stock);

        [$transaction, $detail] = $this->makeCompletedTransactionWithDetail($product, qty: 2);

        $detail->modifiers()->attach($modifier->id, [
            'modifier_name'    => $modifier->name,
            'price_adjustment' => $modifier->price_adjustment,
            'quantity'         => 1,
        ]);

        $this->processReturn($transaction, $detail, returnQty: 2);

        $this->assertEquals(20, $component->fresh()->stock);
        $this->assertDatabaseHas('component_stock_logs', [
            'component_id' => $component->id,
            'type'         => 'return_add',
            'amount'       => 2,
        ]);
    }

    public function test_return_restores_both_bom_and_modifier_stock_together(): void
    {
        $bomComponent      = $this->makeComponent('Bakso Besar', stock: 30);
        $modifierComponent = $this->makeComponent('Kuah Extra', stock: 15);
        $product           = $this->makeProduct();

        ProductBom::create([
            'product_id'   => $product->id,
            'component_id' => $bomComponent->id,
            'quantity'     => 2,
        ]);

        $group = ModifierGroup::create([
            'name'            => 'Tambahan',
            'selection_type'  => 'single',
            'is_required'     => false,
            'is_active'       => true,
        ]);

        $modifier = Modifier::create([
            'modifier_group_id' => $group->id,
            'name'              => 'Kuah Extra',
            'price_adjustment'  => 3000,
            'is_active'         => true,
            'component_id'      => $modifierComponent->id,
        ]);

        // 1 produk terjual: BOM 2x, modifier 1x
        $bomComponent->decrement('stock', 2);
        $modifierComponent->decrement('stock', 1);

        [$transaction, $detail] = $this->makeCompletedTransactionWithDetail($product, qty: 1);

        $detail->modifiers()->attach($modifier->id, [
            'modifier_name'    => $modifier->name,
            'price_adjustment' => $modifier->price_adjustment,
            'quantity'         => 1,
        ]);

        $this->processReturn($transaction, $detail, returnQty: 1);

        $this->assertEquals(30, $bomComponent->fresh()->stock);
        $this->assertEquals(15, $modifierComponent->fresh()->stock);
    }

    public function test_partial_return_restores_proportional_quantity(): void
    {
        $component = $this->makeComponent('Bakso Kecil', stock: 50);
        $product   = $this->makeProduct();

        ProductBom::create([
            'product_id'   => $product->id,
            'component_id' => $component->id,
            'quantity'     => 3,
        ]);

        // 5 produk terjual x 3 = 15 dikurangi
        $component->decrement('stock', 15);
        $this->assertEquals(35, $component->fresh()->stock);

        [$transaction, $detail] = $this->makeCompletedTransactionWithDetail($product, qty: 5);

        // Retur hanya 2 dari 5
        $this->processReturn($transaction, $detail, returnQty: 2);

        // 2 x 3 = 6 dikembalikan, bukan seluruh 15
        $this->assertEquals(41, $component->fresh()->stock);
    }

    public function test_return_still_restores_track_stock_products_unaffected_by_bom_change(): void
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => 'Produk Track Stock',
            'slug'        => 'produk-track-stock',
            'sku'         => 'SKU-TRACK-1',
            'price'       => 10000,
            'is_active'   => true,
            'track_stock' => true,
            'stock'       => 20,
        ]);

        [$transaction, $detail] = $this->makeCompletedTransactionWithDetail($product, qty: 3);

        $product->decrement('stock', 3);
        $this->assertEquals(17, $product->fresh()->stock);

        $this->processReturn($transaction, $detail, returnQty: 3);

        $this->assertEquals(20, $product->fresh()->stock);
    }
}
