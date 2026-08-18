<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Component;
use App\Models\Product;
use App\Models\Unit;
use App\Services\ComponentDemandResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Skenario acuan (memakai contoh nyata dari kebutuhan bisnis):
 *
 *   Bakso Urat = 1 × Bakso Besar Urat + 3 × Bakso Kecil + 1 × Kuah Kaldu
 *   Aturan substitusi: 3 Bakso Kecil  →  2 Bakso Besar Urat
 *
 * Saat substitusi dipakai, kebutuhan Bakso Besar Urat menjadi 1 + 2 = 3.
 * Inilah yang membuat perhitungan per-baris-BOM (cara lama) salah.
 */
class ComponentSubstitutionTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;
    private Component $besar;
    private Component $kecil;
    private Component $kuah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create([
            'name' => 'Menu Utama', 'slug' => 'menu-utama', 'sort_order' => 1, 'is_active' => true,
        ]);

        $this->besar = $this->makeComponent('Bakso Besar Urat', 5);
        $this->kecil = $this->makeComponent('Bakso Kecil', 9);
        $this->kuah  = $this->makeComponent('Kuah Kaldu', 10);
    }

    private function makeComponent(string $name, float $stock): Component
    {
        static $i = 0;
        $i++;

        return Component::create([
            'code'          => 'CMP-' . str_pad((string) $i, 3, '0', STR_PAD_LEFT),
            'name'          => $name,
            'unit_id'       => Unit::firstWhere('symbol', 'pcs')->id,
            'stock'         => $stock,
            'minimum_stock' => 0,
            'cost_price'    => 1000,
            'is_active'     => true,
        ]);
    }

    private function makeBaksoUrat(): Product
    {
        static $i = 0;
        $i++;

        $product = Product::create([
            'category_id' => $this->category->id,
            'name'        => "Bakso Urat {$i}",
            'slug'        => "bakso-urat-{$i}",
            'sku'         => "SKU-BU-{$i}",
            'price'       => 18000,
            'is_active'   => true,
            'track_stock' => true,
            'stock'       => 0, // produk BOM: stok produk tidak dipakai
        ]);

        $product->bom()->create(['component_id' => $this->besar->id, 'quantity' => 1]);
        $product->bom()->create(['component_id' => $this->kecil->id, 'quantity' => 3]);
        $product->bom()->create(['component_id' => $this->kuah->id,  'quantity' => 1]);

        return $product->load('bom.component');
    }

    /** Kasus 1: semua komponen tersedia → komponen pembatas yang menentukan. */
    public function test_estimated_stock_uses_limiting_component(): void
    {
        $product = $this->makeBaksoUrat();

        // besar 5/1 = 5 ; kecil 9/3 = 3 ; kuah 10/1 = 10  → pembatas = 3
        $this->assertSame(3, $product->getBomAvailableQty());
    }

    /** Kasus 2: satu komponen habis tanpa substitusi → stok normal 0. */
    public function test_estimated_stock_is_zero_when_a_component_is_out(): void
    {
        $product = $this->makeBaksoUrat();
        $this->kecil->update(['stock' => 0]);

        $this->assertSame(0, $product->fresh()->load('bom.component')->getBomAvailableQty());
    }

    /**
     * Inti perbaikan: kebutuhan harus DIAGREGASI per komponen sebelum dibagi stok.
     * Bakso Besar Urat dipakai 1× sebagai baris normal DAN 2× sebagai pengganti
     * Bakso Kecil → total 3 per unit produk.
     */
    public function test_substitution_demand_is_aggregated_per_component(): void
    {
        $product = $this->makeBaksoUrat();
        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);

        $subMap = [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 2, 'rule_id' => null],
        ];

        $demand = app(ComponentDemandResolver::class)->demandPerUnit($product, $subMap);

        $this->assertSame(3.0, $demand[$this->besar->id], 'Bakso Besar harus 1 (normal) + 2 (pengganti) = 3');
        $this->assertArrayNotHasKey($this->kecil->id, $demand, 'Bakso Kecil tidak lagi dipakai');
        $this->assertSame(1.0, $demand[$this->kuah->id]);
    }

    /**
     * Perhitungan lama (min per baris BOM) akan menjawab 5 di sini —
     * floor(10/1)=10 untuk baris normal dan floor(10/2)=5 untuk baris pengganti.
     * Jawaban yang benar adalah floor(10/3) = 3.
     */
    public function test_max_producible_uses_aggregated_demand_not_per_line(): void
    {
        $product = $this->makeBaksoUrat();
        $this->besar->update(['stock' => 10]);
        $this->kuah->update(['stock' => 10]);

        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $subMap = [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 2, 'rule_id' => null],
        ];

        $qty = app(ComponentDemandResolver::class)
            ->maxProducibleUnits($product->fresh()->load('bom.component'), $subMap);

        $this->assertSame(3, $qty);
    }

    /** Kebutuhan harus dijumlahkan lintas produk berbeda di keranjang yang sama. */
    public function test_cart_demand_aggregates_across_different_products(): void
    {
        $a = $this->makeBaksoUrat();
        $b = $this->makeBaksoUrat();

        $cart = [
            'a' => ['product_id' => $a->id, 'quantity' => 1, 'modifiers' => []],
            'b' => ['product_id' => $b->id, 'quantity' => 1, 'modifiers' => []],
        ];

        $demand = app(ComponentDemandResolver::class)->demandForCart($cart);

        // 2 produk × 3 Bakso Kecil = 6, padahal stok cuma 9 → masih muat
        $this->assertSame(6.0, $demand[$this->kecil->id]);
        $this->assertSame(2.0, $demand[$this->besar->id]);
    }

    /** Kasus 6: keranjang lintas produk yang melebihi stok harus terdeteksi. */
    public function test_cart_shortfall_detected_across_products(): void
    {
        $a = $this->makeBaksoUrat();
        $b = $this->makeBaksoUrat();

        // kecil 9 → 2 produk × 3 = 6 aman; naikkan jadi 2+2 unit = 12 > 9
        $cart = [
            'a' => ['product_id' => $a->id, 'quantity' => 2, 'modifiers' => []],
            'b' => ['product_id' => $b->id, 'quantity' => 2, 'modifiers' => []],
        ];

        $shortfalls = app(ComponentDemandResolver::class)->shortfalls($cart);

        $this->assertNotEmpty($shortfalls);
        $this->assertSame('Bakso Kecil', $shortfalls[0]['component']);
        $this->assertSame(12.0, $shortfalls[0]['needed']);
        $this->assertSame(9.0, $shortfalls[0]['available']);
    }

    /** Baris BOM dengan qty <= 0 tidak boleh berkontribusi maupun bikin division by zero. */
    public function test_zero_quantity_bom_line_is_ignored(): void
    {
        $product = $this->makeBaksoUrat();
        $product->bom()->where('component_id', $this->kuah->id)->update(['quantity' => 0]);

        $demand = app(ComponentDemandResolver::class)
            ->demandPerUnit($product->fresh()->load('bom.component'));

        $this->assertArrayNotHasKey($this->kuah->id, $demand);
    }

    /** Substitusi dengan product_bom_id basi (BOM sudah berubah) diabaikan, bukan crash. */
    public function test_stale_substitution_key_falls_back_to_normal_composition(): void
    {
        $product = $this->makeBaksoUrat();

        $demand = app(ComponentDemandResolver::class)
            ->demandPerUnit($product, [999999 => ['component_id' => $this->besar->id, 'quantity' => 2]]);

        $this->assertSame(1.0, $demand[$this->besar->id]);
        $this->assertSame(3.0, $demand[$this->kecil->id]);
    }

    // -----------------------------------------------------------------
    // End-to-end: checkout dengan substitusi
    // -----------------------------------------------------------------

    private function checkout(Product $product, array $substitutions, int $qty = 1): \App\Models\Transaction
    {
        \App\Models\Setting::create(['group' => 'general', 'key' => 'tax_percentage', 'value' => '0', 'type' => 'integer']);

        $source  = \App\Models\PaymentSource::create([
            'name' => 'Cash', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1,
        ]);
        $cashier = \App\Models\User::factory()->create(['username' => 'kasir-sub']);

        $payload = new \App\DTOs\Payment\CartPayload(
            cart: [
                "{$product->id}_" => [
                    'product_id'     => $product->id,
                    'product_name'   => $product->name,
                    'unit_price'     => $product->price,
                    'quantity'       => $qty,
                    'modifiers'      => [],
                    'modifier_total' => 0,
                    'subtotal'       => $product->price * $qty,
                    'substitutions'  => $substitutions,
                ],
            ],
            subtotal: $product->price * $qty,
            taxAmount: 0,
            total: $product->price * $qty,
            paidAmount: 1000000,
            changeAmount: 0,
            paymentSourceId: $source->id,
            paymentMethod: 'cash',
            customerName: 'Budi',
            customerPhone: '',
            customerEmail: '',
            orderType: 'take_away',
            serviceAreaId: null,
            pagerId: null,
            notes: '',
            idempotencyKey: (string) \Illuminate\Support\Str::uuid(),
        );

        return app(\App\Actions\Payment\InitiateCashPaymentAction::class)->execute($payload, $cashier->id);
    }

    /**
     * Kasus 3 — komponen habis tetapi punya substitusi.
     * 3 Bakso Kecil → 2 Bakso Besar Urat. Jual 1 produk maka:
     *   Bakso Besar Urat : -3  (1 normal + 2 pengganti)
     *   Bakso Kecil      : -0
     *   Kuah Kaldu       : -1
     */
    public function test_checkout_with_substitution_deducts_actual_composition(): void
    {
        $product = $this->makeBaksoUrat();
        $this->kecil->update(['stock' => 0]);   // komponen normal habis
        $this->besar->update(['stock' => 10]);

        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $rule = $kecilLine->substitutions()->create([
            'component_id' => $this->besar->id, 'quantity' => 2, 'is_active' => true,
        ]);

        $transaction = $this->checkout($product, [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 2, 'rule_id' => $rule->id],
        ]);

        $this->assertSame(7.0, (float) $this->besar->fresh()->stock, 'Bakso Besar harus berkurang 3 (1+2)');
        $this->assertSame(0.0, (float) $this->kecil->fresh()->stock, 'Bakso Kecil tidak boleh berkurang');
        $this->assertSame(9.0, (float) $this->kuah->fresh()->stock, 'Kuah berkurang 1');

        // Komposisi aktual tercatat untuk audit
        $detail   = $transaction->details->first();
        $snapshot = $detail->components->keyBy('component_id');

        $this->assertCount(2, $snapshot, 'Hanya komponen yang benar-benar dipakai yang dicatat');
        $this->assertSame('substitute', $snapshot[$this->besar->id]->source);
        $this->assertSame(3.0, (float) $snapshot[$this->besar->id]->quantity_total);
        $this->assertSame($this->kecil->id, $snapshot[$this->besar->id]->replaced_component_id);
        $this->assertSame('bom', $snapshot[$this->kuah->id]->source);
    }

    /**
     * Retur penjualan bersubstitusi harus mengembalikan komposisi AKTUAL
     * (Besar +3, Kecil +0) — bukan komposisi normal dari BOM yang hidup
     * (Besar +1, Kecil +3), yang akan menciptakan stok hantu.
     */
    public function test_return_of_substituted_sale_restores_actual_composition(): void
    {
        $product = $this->makeBaksoUrat();
        $this->kecil->update(['stock' => 0]);
        $this->besar->update(['stock' => 10]);

        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $rule = $kecilLine->substitutions()->create([
            'component_id' => $this->besar->id, 'quantity' => 2, 'is_active' => true,
        ]);

        $transaction = $this->checkout($product, [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 2, 'rule_id' => $rule->id],
        ]);

        $detail = $transaction->details->first();

        app(\App\Services\ComponentStockService::class)
            ->restoreForReturn($detail->id, 1, null, 999);

        $this->assertSame(10.0, (float) $this->besar->fresh()->stock, 'Besar kembali ke 10 (7 + 3)');
        $this->assertSame(0.0, (float) $this->kecil->fresh()->stock, 'Kecil TIDAK boleh bertambah');
        $this->assertSame(10.0, (float) $this->kuah->fresh()->stock);
    }

    /** Kasus 4 — stok komponen pengganti tidak mencukupi → transaksi ditolak. */
    public function test_checkout_rejected_when_substitute_component_insufficient(): void
    {
        $product = $this->makeBaksoUrat();
        $this->kecil->update(['stock' => 0]);
        $this->besar->update(['stock' => 2]); // butuh 3, hanya ada 2

        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $rule = $kecilLine->substitutions()->create([
            'component_id' => $this->besar->id, 'quantity' => 2, 'is_active' => true,
        ]);

        $this->expectException(\App\Exceptions\InsufficientStockException::class);

        $this->checkout($product, [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 2, 'rule_id' => $rule->id],
        ]);
    }

    /** Client tidak boleh menentukan qty/komponen sendiri saat memakai rule_id. */
    public function test_tampered_substitution_quantity_is_overridden_by_database_rule(): void
    {
        $product = $this->makeBaksoUrat();
        $this->kecil->update(['stock' => 0]);
        $this->besar->update(['stock' => 10]);

        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $rule = $kecilLine->substitutions()->create([
            'component_id' => $this->besar->id, 'quantity' => 2, 'is_active' => true,
        ]);

        // Client mencoba mengaku hanya butuh 0.001 Bakso Besar.
        $this->checkout($product, [
            $kecilLine->id => ['component_id' => $this->besar->id, 'quantity' => 0.001, 'rule_id' => $rule->id],
        ]);

        // Nilai dari DB (2) yang dipakai → total terpotong tetap 3.
        $this->assertSame(7.0, (float) $this->besar->fresh()->stock);
    }

    /**
     * Regresi: syncBomItems() versi lama melakukan delete-and-recreate, sehingga
     * cascadeOnDelete menghapus SELURUH aturan substitusi setiap kali produk
     * disimpan — bahkan saat admin hanya mengubah harga.
     */
    public function test_editing_product_preserves_substitution_rules_and_bom_line_ids(): void
    {
        $product = $this->makeBaksoUrat();
        $kecilLine = $product->bom->firstWhere('component_id', $this->kecil->id);
        $originalLineId = $kecilLine->id;

        $kecilLine->substitutions()->create([
            'component_id' => $this->besar->id, 'quantity' => 2, 'is_active' => true,
        ]);

        $admin = \App\Models\User::factory()->create(['username' => 'admin-sub']);
        $admin->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'edit_products']));

        // Admin hanya mengubah harga — BOM tidak disentuh sama sekali.
        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\Products::class)
            ->call('edit', $product->id)
            ->set('price', 20000)
            ->call('save');

        $kecilLine->refresh();

        $this->assertSame($originalLineId, $kecilLine->id, 'product_bom.id harus stabil');
        $this->assertCount(1, $kecilLine->substitutions, 'Aturan substitusi tidak boleh hilang');
        $this->assertSame(20000.0, (float) $product->fresh()->price);
    }

    /** product_bom_id milik produk lain harus ditolak (anti injeksi lintas produk). */
    public function test_substitution_referencing_foreign_bom_line_is_rejected(): void
    {
        $product = $this->makeBaksoUrat();
        $other   = $this->makeBaksoUrat();
        $foreignLine = $other->bom->first();

        $this->expectException(\RuntimeException::class);

        $this->checkout($product, [
            $foreignLine->id => ['component_id' => $this->besar->id, 'quantity' => 1, 'rule_id' => null],
        ]);
    }
}
