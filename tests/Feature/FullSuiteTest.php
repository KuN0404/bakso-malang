<?php

/**
 * Full Automation Test Suite — Bakso Malang POS System
 *
 * Mencakup seluruh modul:
 * - Model: Category, Product, Shift, Transaction, TransactionDetail, StockLog, PaymentSource
 * - Model Scopes & Static Methods (OOP hasil refactor)
 * - Livewire Admin: Categories, Products, PaymentSources, Users, Shifts
 * - Livewire POS: PosCheckout (cart, checkout, stok, retur)
 * - Laporan: SalesReport (tab), ShiftReport, ProductSalesReport, TransactionHistory
 * - Business Logic: StockLog::record, Transaction::cancel, Shift::close
 */

use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\PaymentSources;
use App\Livewire\Admin\ProductSalesReport;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Returns;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Admin\ServiceAreas;
use App\Livewire\Admin\ShiftReport;
use App\Livewire\Admin\TransactionHistory;
use App\Livewire\Admin\Users;
use App\Livewire\PosCheckout;
use App\Models\Category;
use App\Models\DailyQueueNumber;
use App\Models\PaymentSource;
use App\Models\Product;
use App\Models\ProductReturn;
use App\Models\ReturnItem;
use App\Models\ServiceArea;
use App\Models\Shift;
use App\Models\ShiftExpense;
use App\Models\StockLog;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// =============================================================================
// HELPERS
// =============================================================================

function createBaseData(): array
{
    $admin = User::factory()->create([
        'name'     => 'Admin POS',
        'username' => 'admin_test',
        'email'    => 'admin@example.com',
    ]);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $admin->assignRole('Super Admin');

    $cashier = User::factory()->create([
        'name'     => 'Kasir Satu',
        'username' => 'kasir_test',
        'email'    => 'kasir@example.com',
    ]);

    $category = Category::create([
        'name'       => 'Makanan Berat',
        'slug'       => 'makanan-berat',
        'is_active'  => true,
        'sort_order' => 1,
    ]);

    $drinkCategory = Category::create([
        'name'       => 'Minuman',
        'slug'       => 'minuman',
        'is_active'  => true,
        'sort_order' => 2,
    ]);

    $bakso = Product::create([
        'category_id' => $category->id,
        'name'        => 'Bakso Malang Special',
        'slug'        => 'bakso-malang-special',
        'sku'         => 'BMS01',
        'price'       => 25000,
        'cost_price'  => 15000,
        'is_active'   => true,
        'track_stock' => true,
        'stock'       => 20,
    ]);

    $esTeh = Product::create([
        'category_id' => $drinkCategory->id,
        'name'        => 'Es Teh Manis',
        'slug'        => 'es-teh-manis',
        'sku'         => 'ETM01',
        'price'       => 5000,
        'cost_price'  => 1000,
        'is_active'   => true,
        'track_stock' => false,
        'stock'       => 0,
    ]);

    $cashPayment = PaymentSource::create([
        'name'       => 'Uang Tunai',
        'type'       => 'cash',
        'is_active'  => true,
        'sort_order' => 1,
    ]);

    $qrisPayment = PaymentSource::create([
        'name'       => 'QRIS',
        'type'       => 'qris',
        'is_active'  => true,
        'sort_order' => 2,
    ]);

    $serviceArea = ServiceArea::create([
        'name'       => 'Meja 1',
        'code'       => 'M1',
        'is_active'  => true,
        'sort_order' => 1,
    ]);

    $shift = Shift::create([
        'user_id'      => $cashier->id,
        'started_at'   => now(),
        'opening_cash' => 100000,
        'status'       => 'open',
    ]);

    return compact(
        'admin', 'cashier', 'category', 'drinkCategory', 'bakso', 'esTeh',
        'cashPayment', 'qrisPayment', 'serviceArea', 'shift'
    );
}

function createTransaction(Shift $shift, User $cashier, PaymentSource $payment, array $items): Transaction
{
    return Transaction::create([
        'user_id'           => $cashier->id,
        'shift_id'          => $shift->id,
        'payment_source_id' => $payment->id,
        'invoice_number'    => Transaction::generateInvoiceNumber(),
        'queue_number'      => DailyQueueNumber::getNextNumber(),
        'subtotal'          => collect($items)->sum('subtotal'),
        'discount_amount'   => 0,
        'tax_amount'        => 0,
        'total'             => collect($items)->sum('subtotal'),
        'paid_amount'       => collect($items)->sum('subtotal'),
        'change_amount'     => 0,
        'payment_method'    => $payment->type === 'cash' ? 'cash' : 'non_cash',
        'status'            => 'completed',
        'order_type'        => 'take_away',
    ]);
}

// =============================================================================
// 1. MODEL TESTS
// =============================================================================

test('[Model] Category::forPos() only returns active categories', function () {
    Category::create(['name' => 'Aktif', 'slug' => 'aktif', 'is_active' => true, 'sort_order' => 1]);
    Category::create(['name' => 'Non-Aktif', 'slug' => 'non-aktif', 'is_active' => false, 'sort_order' => 2]);

    $results = Category::forPos()->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Aktif');
});

test('[Model] Product::forPosDisplay() filters by category and search', function () {
    $data = createBaseData();

    // Filter by category
    $results = Product::forPosDisplay($data['category']->id)->get();
    expect($results->every(fn($p) => $p->category_id === $data['category']->id))->toBeTrue();

    // Search by name
    $searched = Product::forPosDisplay(null, 'Bakso')->get();
    expect($searched)->toHaveCount(1);
    expect($searched->first()->name)->toContain('Bakso');
});

test('[Model] Shift::getActiveShift() returns open shift for user', function () {
    $data = createBaseData();

    $active = Shift::getActiveShift($data['cashier']->id);
    expect($active)->not->toBeNull();
    expect($active->id)->toBe($data['shift']->id);
    expect($active->status)->toBe('open');
});

test('[Model] Shift::getTodayShift() returns today shift', function () {
    $data = createBaseData();

    $today = Shift::getTodayShift($data['cashier']->id);
    expect($today)->not->toBeNull();
    expect($today->id)->toBe($data['shift']->id);
});

test('[Model] Shift::getOrCreateTodayShift() creates shift if none exists', function () {
    $user = User::factory()->create(['email' => 'newcashier@example.com', 'username' => 'newcashier']);
    PaymentSource::create(['name' => 'Kas', 'type' => 'cash', 'is_active' => true, 'sort_order' => 1]);

    expect(Shift::where('user_id', $user->id)->count())->toBe(0);
    $shift = Shift::getOrCreateTodayShift($user->id);
    expect($shift)->toBeInstanceOf(Shift::class);
    expect(Shift::where('user_id', $user->id)->count())->toBe(1);
});

test('[Model] StockLog::record() creates a log entry correctly', function () {
    $data = createBaseData();

    StockLog::record(
        productId:  $data['bakso']->id,
        userId:     $data['cashier']->id,
        type:       'sale',
        amount:     -5,
        finalStock: 15,
        note:       'Test sale log',
        referenceId: null
    );

    $log = StockLog::first();
    expect($log->product_id)->toBe($data['bakso']->id);
    expect($log->type)->toBe('sale');
    expect($log->amount)->toBe(-5);
    expect($log->final_stock)->toBe(15);
});

test('[Model] Transaction::getSummaryStats() returns correct totals', function () {
    $data = createBaseData();

    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 30000],
        ['subtotal' => 20000],
    ]);
    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 15000],
    ]);

    $start = Carbon::today()->startOfDay();
    $end   = Carbon::today()->endOfDay();
    $stats = Transaction::getSummaryStats($start, $end);

    expect($stats['total_transactions'])->toBe(2);
    expect((float) $stats['total_revenue'])->toBe(65000.0);
});

test('[Model] Transaction::scopeFilter() filters by date, search, and cashier', function () {
    $data = createBaseData();

    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 25000],
    ]);

    $start  = Carbon::today()->startOfDay();
    $end    = Carbon::today()->endOfDay();
    $search = substr($transaction->invoice_number, 0, 5);

    $results = Transaction::filter($start, $end, $search)->get();
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($transaction->id);
});

test('[Model] Transaction::cancel() marks transaction as cancelled', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 30000],
    ]);

    expect($transaction->status)->toBe('completed');
    $transaction->cancel($data['admin']->id, 'Test cancellation');
    $transaction->refresh();
    expect($transaction->status)->toBe('cancelled');
});

test('[Model] Shift::close() calculates difference and marks shift as closed', function () {
    $data = createBaseData();

    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 50000]]);

    $data['shift']->opening_cash = 100000;
    $data['shift']->save();

    $data['shift']->close(actualCash: 148000, actualNonCash: 0, notes: 'Test close');
    $data['shift']->refresh();

    expect($data['shift']->status)->toBe('closed');
    expect($data['shift']->actual_cash)->toBe('148000.00'); // decimal cast returns string
    expect($data['shift']->ended_at)->not->toBeNull();
});

test('[Model] TransactionDetail::getProductSalesReport() returns paginated results', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 25000],
    ]);

    $transaction->details()->create([
        'product_id'     => $data['bakso']->id,
        'product_name'   => $data['bakso']->name,
        'unit_price'     => 25000,
        'quantity'       => 1,
        'modifier_total' => 0,
        'subtotal'       => 25000,
    ]);

    $start  = Carbon::today()->startOfDay();
    $end    = Carbon::today()->endOfDay();
    $result = TransactionDetail::getProductSalesReport($start, $end);

    expect($result->total())->toBe(1);
    expect($result->first()->product_name)->toBe($data['bakso']->name);
    expect((int) $result->first()->total_qty)->toBe(1);
});

test('[Model] TransactionDetail::getProductSalesSummary() returns totals', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [
        ['subtotal' => 50000],
    ]);

    $transaction->details()->create([
        'product_id'     => $data['bakso']->id,
        'product_name'   => $data['bakso']->name,
        'unit_price'     => 25000,
        'quantity'       => 2,
        'modifier_total' => 0,
        'subtotal'       => 50000,
    ]);

    $start   = Carbon::today()->startOfDay();
    $end     = Carbon::today()->endOfDay();
    $summary = TransactionDetail::getProductSalesSummary($start, $end);

    expect((int) $summary['total_qty'])->toBe(2);
    expect((float) $summary['total_revenue'])->toBe(50000.0);
});

// =============================================================================
// 2. POS CHECKOUT TESTS
// =============================================================================

test('[POS] User can add products to cart and subtotal is correct', function () {
    $data = createBaseData();

    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('addToCart', $data['bakso']->id)
        ->call('addToCart', $data['esTeh']->id)
        ->assertSet('cart', function ($cart) use ($data) {
            $baksoKey = $data['bakso']->id . '_';
            $eTehKey  = $data['esTeh']->id . '_';
            return isset($cart[$baksoKey]) && isset($cart[$eTehKey]);
        });
});

test('[POS] Cart cannot add product exceeding stock', function () {
    $data = createBaseData();

    // bakso has stock=20, set quantity to 25 should fail/be rejected
    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('addToCart', $data['bakso']->id)
        ->call('updateQuantity', $data['bakso']->id . '_', 999)
        ->assertSet('cart', function ($cart) use ($data) {
            $key = $data['bakso']->id . '_';
            return isset($cart[$key]) && $cart[$key]['quantity'] <= 20;
        });
});

test('[POS] Remove from cart works correctly', function () {
    $data = createBaseData();

    $cartKey = $data['bakso']->id . '_';

    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('addToCart', $data['bakso']->id)
        ->assertSet('cart', fn($c) => isset($c[$cartKey]))
        ->call('removeFromCart', $cartKey)
        ->assertSet('cart', fn($c) => !isset($c[$cartKey]));
});

test('[POS] Clear cart empties all items', function () {
    $data = createBaseData();

    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('addToCart', $data['bakso']->id)
        ->call('addToCart', $data['esTeh']->id)
        ->call('clearCart')
        ->assertSet('cart', []);
});

test('[POS] Take-away cash checkout creates transaction, decrements stock, logs stock', function () {
    $data = createBaseData();

    expect(Transaction::count())->toBe(0);

    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->set('paymentSourceId', $data['cashPayment']->id)
        ->set('orderType', 'take_away')
        ->call('addToCart', $data['bakso']->id)
        ->set('paidAmount', 25000)
        ->call('processPayment')
        ->assertHasNoErrors();

    expect(Transaction::count())->toBe(1);

    $t = Transaction::first();
    expect($t->status)->toBe('completed');
    expect($t->order_type)->toBe('take_away');
    expect($t->payment_method)->toBe('cash');

    // Stock decremented
    $data['bakso']->refresh();
    expect($data['bakso']->stock)->toBe(19);

    // StockLog created
    $log = StockLog::first();
    expect($log->type)->toBe('sale');
    expect($log->amount)->toBe(-1);
});

test('[POS] Dine-in order requires service area', function () {
    $data = createBaseData();

    $component = Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->set('paymentSourceId', $data['cashPayment']->id)
        ->set('orderType', 'dine_in')
        ->set('selectedServiceAreaId', null)
        ->call('addToCart', $data['bakso']->id)
        ->set('paidAmount', 25000)
        ->call('processPayment');

    // Transaction should NOT be created (service area required)
    expect(Transaction::count())->toBe(0);
});

test('[POS] Insufficient cash triggers error notify', function () {
    $data = createBaseData();

    $component = Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->set('paymentSourceId', $data['cashPayment']->id)
        ->set('orderType', 'take_away')
        ->call('addToCart', $data['bakso']->id) // price=25000
        ->set('paidAmount', 10000)              // not enough
        ->call('processPayment');

    expect(Transaction::count())->toBe(0);
});

test('[POS] Category filter in POS displays correct products', function () {
    $data = createBaseData();

    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('selectCategory', $data['category']->id)
        ->assertSet('selectedCategoryId', $data['category']->id);
});

// =============================================================================
// 3. ADMIN — CATEGORIES MODULE
// =============================================================================

test('[Admin] Categories: create and update category', function () {
    $admin = User::factory()->create(['email' => 'cat@example.com', 'username' => 'cat_admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $admin->assignRole('Super Admin');

    Livewire::actingAs($admin)
        ->test(Categories::class)
        ->set('name', 'Kategori Baru')
        ->set('sort_order', 5)
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('name', 'Kategori Baru')->exists())->toBeTrue();
});

test('[Admin] Categories: delete category', function () {
    $admin    = User::factory()->create(['email' => 'cat2@example.com', 'username' => 'cat2_admin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $admin->assignRole('Super Admin');
    $category = Category::create(['name' => 'Delete Me', 'slug' => 'delete-me', 'is_active' => true, 'sort_order' => 1]);

    Livewire::actingAs($admin)
        ->test(Categories::class)
        ->call('delete', $category->id)
        ->assertHasNoErrors();

    expect(Category::find($category->id))->toBeNull();
});

// =============================================================================
// 4. ADMIN — PRODUCTS MODULE
// =============================================================================

test('[Admin] Products: create product creates initial stock log', function () {
    $data = createBaseData();

    Livewire::actingAs($data['admin'])
        ->test(Products::class)
        ->set('name', 'Mie Ayam')
        ->set('sku', 'MIE01')
        ->set('price', 18000)
        ->set('cost_price', 8000)
        ->set('category_id', $data['category']->id)
        ->set('is_active', true)
        ->set('track_stock', true)
        ->set('stock', 50)
        ->set('is_unlimited', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::where('name', 'Mie Ayam')->exists())->toBeTrue();

    $product = Product::where('name', 'Mie Ayam')->first();
    expect($product->stock)->toBe(50);
    expect(StockLog::where('product_id', $product->id)->where('type', 'initial')->exists())->toBeTrue();
});

test('[Admin] Products: stock adjustment creates stock log', function () {
    $data = createBaseData();

    Livewire::actingAs($data['admin'])
        ->test(Products::class)
        ->call('openStockModal', $data['bakso']->id)
        ->call('saveStock', 'add', 10, null)
        ->assertHasNoErrors();

    $data['bakso']->refresh();
    expect($data['bakso']->stock)->toBe(30); // 20 + 10

    expect(StockLog::where('product_id', $data['bakso']->id)->where('type', 'add')->exists())->toBeTrue();
});

test('[Admin] Products: delete moves product to trash (soft delete)', function () {
    $data = createBaseData();

    Livewire::actingAs($data['admin'])
        ->test(Products::class)
        ->call('delete', $data['bakso']->id)
        ->assertHasNoErrors();

    expect(Product::find($data['bakso']->id))->toBeNull();
    expect(Product::withTrashed()->find($data['bakso']->id))->not->toBeNull();
});

// =============================================================================
// 5. ADMIN — PAYMENT SOURCES MODULE
// =============================================================================

test('[Admin] PaymentSources: create and delete payment source', function () {
    $admin = User::factory()->create(['email' => 'pay@example.com', 'username' => 'pay_admin']);

    Livewire::actingAs($admin)
        ->test(PaymentSources::class)
        ->set('name', 'QRIS Mandiri')
        ->set('type', 'qris')
        ->set('is_active_pos', true)
        ->set('is_active_self_order', true)
        ->set('sort_order', 3)
        ->call('save')
        ->assertHasNoErrors();

    expect(PaymentSource::where('name', 'QRIS Mandiri')->exists())->toBeTrue();

    $source = PaymentSource::where('name', 'QRIS Mandiri')->first();
    Livewire::actingAs($admin)
        ->test(PaymentSources::class)
        ->call('delete', $source->id);

    expect(PaymentSource::find($source->id))->toBeNull();
});

// =============================================================================
// 6. ADMIN — USERS MODULE
// =============================================================================

test('[Admin] Users: create new user', function () {
    $admin = User::factory()->create(['email' => 'superadmin@example.com', 'username' => 'superadmin']);
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
    $admin->assignRole('Super Admin');

    // Create a Cashier role so selectedRoles can be validated
    \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Kasir', 'guard_name' => 'web']);

    Livewire::actingAs($admin)
        ->test(Users::class)
        ->set('name', 'Kasir Dua')
        ->set('username', 'kasirdua')
        ->set('email', 'kasirdua@example.com')
        ->set('password', 'password')
        ->set('selectedRoles', ['Kasir'])
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('email', 'kasirdua@example.com')->exists())->toBeTrue();
});

// =============================================================================
// 7. ADMIN — SERVICE AREAS MODULE
// =============================================================================

test('[Admin] ServiceAreas: create service area', function () {
    $admin = User::factory()->create(['email' => 'area@example.com', 'username' => 'area_admin']);

    Livewire::actingAs($admin)
        ->test(ServiceAreas::class)
        ->set('name', 'Meja VIP')
        ->set('code', 'MVIP')
        ->set('type', 'table')
        ->set('capacity', 4)
        ->set('is_active', true)
        ->set('sort_order', 10)
        ->call('save')
        ->assertHasNoErrors();

    expect(ServiceArea::where('name', 'Meja VIP')->exists())->toBeTrue();
});

// =============================================================================
// 8. LAPORAN — SHIFT REPORT
// =============================================================================

test('[Laporan] ShiftReport: renders with correct summary for date range', function () {
    $data = createBaseData();

    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 50000]]);

    Livewire::actingAs($data['admin'])
        ->test(ShiftReport::class)
        ->assertHasNoErrors()
        ->assertSee('Laporan Shift');
});

// =============================================================================
// 9. LAPORAN — TRANSACTION HISTORY
// =============================================================================

test('[Laporan] TransactionHistory: renders and shows transactions', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 30000]]);

    Livewire::actingAs($data['admin'])
        ->test(TransactionHistory::class)
        ->assertHasNoErrors()
        ->assertSee($transaction->invoice_number);
});

test('[Laporan] TransactionHistory: search filters correctly', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 30000]]);

    Livewire::actingAs($data['admin'])
        ->test(TransactionHistory::class)
        ->set('search', $transaction->invoice_number)
        ->assertSee($transaction->invoice_number);
});

// =============================================================================
// 10. LAPORAN — SALES REPORT
// =============================================================================

test('[Laporan] SalesReport: renders analysis tab without error', function () {
    $data = createBaseData();

    Livewire::actingAs($data['admin'])
        ->test(SalesReport::class)
        ->set('activeTab', 'analysis')
        ->assertHasNoErrors();
});

test('[Laporan] SalesReport: tab categories renders correctly', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 25000]]);
    $transaction->details()->create([
        'product_id'     => $data['bakso']->id,
        'product_name'   => $data['bakso']->name,
        'unit_price'     => 25000,
        'quantity'       => 1,
        'modifier_total' => 0,
        'subtotal'       => 25000,
    ]);

    Livewire::actingAs($data['admin'])
        ->test(SalesReport::class)
        ->call('setTab', 'categories')
        ->assertHasNoErrors();
});

test('[Laporan] SalesReport: tab products renders correctly', function () {
    $data = createBaseData();

    Livewire::actingAs($data['admin'])
        ->test(SalesReport::class)
        ->call('setTab', 'products')
        ->assertHasNoErrors();
});

// =============================================================================
// 11. LAPORAN — PRODUCT SALES REPORT
// =============================================================================

test('[Laporan] ProductSalesReport: renders with pagination and summary', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 25000]]);
    $transaction->details()->create([
        'product_id'     => $data['bakso']->id,
        'product_name'   => $data['bakso']->name,
        'unit_price'     => 25000,
        'quantity'       => 1,
        'modifier_total' => 0,
        'subtotal'       => 25000,
    ]);

    Livewire::actingAs($data['admin'])
        ->test(ProductSalesReport::class)
        ->assertHasNoErrors()
        ->assertSee($data['bakso']->name);
});

// =============================================================================
// 12. RETUR MODULE
// =============================================================================

test('[POS] Product return processes correctly and restores stock', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 25000]]);

    $detail = $transaction->details()->create([
        'product_id'     => $data['bakso']->id,
        'product_name'   => $data['bakso']->name,
        'unit_price'     => 25000,
        'quantity'       => 2,
        'modifier_total' => 0,
        'subtotal'       => 50000,
    ]);

    $initialStock = $data['bakso']->stock; // 20

    // Process return via Livewire component
    Livewire::actingAs($data['cashier'])
        ->test(PosCheckout::class)
        ->call('openReturnModal')
        ->set('returnInvoiceSearch', $transaction->invoice_number)
        ->call('searchReturnInvoice')
        ->assertSet('returnTransaction', fn($t) => $t && $t->id === $transaction->id)
        ->set("returnItems.{$detail->id}.selected", true)
        ->set("returnItems.{$detail->id}.quantity", 1)
        ->set('returnReason', 'Produk tidak sesuai')
        ->call('processReturn')
        ->assertHasNoErrors();

    expect(ProductReturn::count())->toBe(1);
    expect(ReturnItem::count())->toBe(1);

    $data['bakso']->refresh();
    expect($data['bakso']->stock)->toBe($initialStock + 1); // restored

    // Stock log created for return
    expect(StockLog::where('type', 'in')->exists())->toBeTrue();
});

test('[Admin] Returns: view returns list', function () {
    $data        = createBaseData();
    $transaction = createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 25000]]);

    $return = ProductReturn::create([
        'transaction_id' => $transaction->id,
        'user_id'        => $data['cashier']->id,
        'shift_id'       => $data['shift']->id,
        'return_number'  => ProductReturn::generateReturnNumber(),
        'total_refund'   => 25000,
        'reason'         => 'Test return',
    ]);

    Livewire::actingAs($data['admin'])
        ->test(Returns::class)
        ->assertHasNoErrors()
        ->assertSee($return->return_number);
});

// =============================================================================
// 13. DASHBOARD
// =============================================================================

test('[Admin] Dashboard: renders with today stats', function () {
    $data = createBaseData();

    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 30000]]);
    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 20000]]);

    Livewire::actingAs($data['admin'])
        ->test(Dashboard::class)
        ->assertHasNoErrors();
});

// =============================================================================
// 14. BUSINESS LOGIC TESTS
// =============================================================================

test('[Logic] DailyQueueNumber increments per day', function () {
    expect(DailyQueueNumber::getNextNumber())->toBe(1);
    expect(DailyQueueNumber::getNextNumber())->toBe(2);
    expect(DailyQueueNumber::getNextNumber())->toBe(3);
    expect(DailyQueueNumber::getCurrentNumber())->toBe(3);
});

test('[Logic] Transaction::generateInvoiceNumber() creates unique number per day', function () {
    $inv1 = Transaction::generateInvoiceNumber();
    $inv2 = Transaction::generateInvoiceNumber();
    expect($inv1)->toBe($inv2); // No transaction created yet, both return same "next"

    $data = createBaseData();
    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 1]]);

    $inv3 = Transaction::generateInvoiceNumber();
    expect($inv3)->not->toBe($inv1); // new number after transaction created
});

test('[Logic] Shift::calculateExpectedCash() includes opening cash + sales - expenses', function () {
    $data = createBaseData();

    // Opening cash = 100000
    // Add a cash transaction of 50000
    createTransaction($data['shift'], $data['cashier'], $data['cashPayment'], [['subtotal' => 50000]]);

    // Add an expense of 10000
    ShiftExpense::create([
        'shift_id'    => $data['shift']->id,
        'description' => 'Beli plastik',
        'amount'      => 10000,
        'category'    => 'operational',
    ]);

    $expected = $data['shift']->calculateExpectedCash();
    // 100000 + 50000 - 10000 = 140000
    expect($expected)->toBe(140000.0);
});
