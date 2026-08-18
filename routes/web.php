<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Components;
use App\Livewire\Admin\Customers;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Ingredients;
use App\Livewire\Admin\InventoryReport;
use App\Livewire\Admin\Modifiers;
use App\Livewire\Admin\PaymentSources;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\ProductSalesReport;
use App\Livewire\Admin\Productions;
use App\Livewire\Admin\PurchaseCreate;
use App\Livewire\Admin\PurchaseDetail;
use App\Livewire\Admin\Purchases;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\HppCalculator;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\ShiftReport;
use App\Livewire\Admin\Shifts;
use App\Livewire\Admin\TransactionHistory;
use App\Livewire\Admin\Users;
use App\Livewire\Admin\Whatsapp;
use App\Livewire\PosCheckout;
use Illuminate\Support\Facades\Route;

// Public Landing Page (Beranda)
Route::get('/', App\Livewire\LandingPage::class)->name('home');

// Public Digital Menu (No Login Required)
Route::get('/menu', App\Livewire\PublicMenu::class)->name('menu.public');

// Login
Route::get('/login', [App\Http\Controllers\LoginController::class, 'showLoginForm'])->name('login')->middleware('guest');
Route::post('/login', [App\Http\Controllers\LoginController::class, 'login'])->name('login.post')->middleware('guest');

// Logout — must be auth so CSRF session is still valid
Route::middleware('auth')->group(function () {
    Route::post('/logout', [App\Http\Controllers\LoginController::class, 'logout'])->name('logout');
});

// Admin Routes
// PENTING: setiap halaman admin WAJIB punya middleware can:xxx yang sesuai — sebelumnya
// hanya dilindungi 'auth', jadi role apa pun (termasuk Kasir/Kitchen) yang login bisa buka
// /admin/users, /admin/roles, /admin/settings, laporan keuangan, dll langsung lewat URL,
// menembus sistem permission yang sebenarnya sudah didesain granular di RolesAndPermissionsSeeder.
Route::middleware(['auth', 'throttle:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/menu', App\Livewire\Admin\MenuCatalog::class)->name('menu.index')->middleware('can:view_menu_catalog');
    Route::get('/hpp-calculator', HppCalculator::class)->name('hpp-calculator.index')->middleware('can:view_hpp_calculator');

    // Master Data
    Route::get('/categories', Categories::class)->name('categories.index')->middleware('can:view_categories');
    Route::get('/units', App\Livewire\Admin\Units::class)->name('units.index')->middleware('can:view_units');
    Route::get('/products', Products::class)->name('products.index')->middleware('can:view_products');
    Route::get('/products/{product}', App\Livewire\Admin\ProductDetail::class)->name('products.show')->middleware('can:view_products');
    Route::get('/modifiers', Modifiers::class)->name('modifiers.index')->middleware('can:view_modifiers');
    Route::get('/payment-sources', PaymentSources::class)->name('payment-sources.index')->middleware('can:manage_payment_sources');
    Route::get('/service-areas', App\Livewire\Admin\ServiceAreas::class)->name('service-areas.index')->middleware('can:manage_service_areas');
    Route::get('/pagers', App\Livewire\Admin\Pagers::class)->name('pagers.index')->middleware('can:manage_pagers');

    // Inventory & Production
    Route::get('/ingredients', Ingredients::class)->name('ingredients.index')->middleware('can:view_ingredients');
    Route::get('/components', Components::class)->name('components.index')->middleware('can:view_components');
    Route::get('/purchases', Purchases::class)->name('purchases.index')->middleware('can:view_purchases');
    Route::get('/purchases/create', PurchaseCreate::class)->name('purchases.create')->middleware('can:create_purchases');
    Route::get('/purchases/{purchase}', PurchaseDetail::class)->name('purchases.show')->middleware('can:view_purchases');
    Route::get('/suppliers', App\Livewire\Admin\Suppliers::class)->name('suppliers.index')->middleware('can:view_suppliers');
    Route::get('/productions', Productions::class)->name('productions.index')->middleware('can:view_productions');

    // Transactions & Shifts
    Route::get('/shifts', Shifts::class)->name('shifts.index')->middleware('can:view_own_shifts');
    Route::get('/returns', App\Livewire\Admin\Returns::class)->name('returns')->middleware('can:view_returns');

    // Pelanggan & Blacklist Nomor HP
    Route::get('/customers', Customers::class)->name('customers.index')->middleware('can:view_customers');

    // Reports
    Route::get('/reports/transactions', TransactionHistory::class)->name('reports.transactions')->middleware('can:view_transactions');
    Route::get('/reports/sales', SalesReport::class)->name('reports.sales')->middleware('can:view_sales_reports');
    Route::get('/reports/products', ProductSalesReport::class)->name('reports.products')->middleware('can:view_sales_reports');
    Route::get('/reports/inventory', InventoryReport::class)->name('reports.inventory')->middleware('can:view_inventory_reports');
    Route::get('/reports/shifts', ShiftReport::class)->name('reports.shifts')->middleware('can:view_all_shifts');

    // Settings
    Route::get('/users', Users::class)->name('users.index')->middleware('can:view_users');
    Route::get('/roles', Roles::class)->name('roles.index')->middleware('can:manage_roles');
    Route::get('/settings', Settings::class)->name('settings.index')->middleware('can:manage_settings');
    Route::get('/whatsapp', Whatsapp::class)->name('whatsapp.index')->middleware('can:manage_whatsapp');
});

// Print Routes
// Sama seperti halaman admin di atas — dulu cuma 'auth', jadi siapa pun yang login bisa akses
// URL print langsung tanpa pernah melewati halaman laporan yang (sekarang) di-gate izinnya.
Route::middleware(['auth'])->prefix('print')->name('print.')->group(function () {
    Route::get('/transactions/table', [App\Http\Controllers\PrintController::class, 'transactionsTable'])->name('transactions.table')->middleware('can:view_transactions');
    Route::get('/transactions/detail', [App\Http\Controllers\PrintController::class, 'transactionsDetail'])->name('transactions.detail')->middleware('can:view_transactions');
    Route::get('/returns-report', [App\Http\Controllers\PrintController::class, 'returnsReport'])->name('returns-report')->middleware('can:view_returns');
    Route::get('/return-detail/{id}', [App\Http\Controllers\PrintController::class, 'returnDetail'])->name('return-detail')->middleware('can:view_returns');
    Route::get('/sales-report', [App\Http\Controllers\PrintController::class, 'salesReport'])->name('sales-report')->middleware('can:view_sales_reports');
    Route::get('/inventory-report', [App\Http\Controllers\PrintController::class, 'inventoryReport'])->name('inventory-report')->middleware('can:view_inventory_reports');
    Route::get('/transaction/{transaction}', [App\Http\Controllers\PrintController::class, 'transactionSingle'])->name('transaction.single')->middleware('can:view_transactions');
    Route::get('/shifts/table', [App\Http\Controllers\PrintController::class, 'shiftsTable'])->name('shifts.table')->middleware('can:view_all_shifts');
    Route::get('/shift/{shift}', [App\Http\Controllers\PrintController::class, 'shiftDetail'])->name('shift.detail')->middleware('can:view_all_shifts');
    Route::get('/shift/{shift}/custom', [App\Http\Controllers\PrintController::class, 'shiftCustom'])->name('shift.custom')->middleware('can:view_all_shifts');
});

// Export Routes (separate for streaming)
Route::middleware(['auth', 'throttle:5,1'])->name('export.')->prefix('export')->group(function () {
    Route::get('/transactions', [ExportController::class, 'transactions'])->name('transactions')->middleware('can:view_transactions');
    Route::get('/transactions-detail', [ExportController::class, 'transactionsDetail'])->name('transactions.detail')->middleware('can:view_transactions');
    Route::get('/product-sales', [ExportController::class, 'productSales'])->name('product-sales')->middleware('can:view_sales_reports');
    Route::get('/sales-by-category', [ExportController::class, 'salesByCategory'])->name('sales-by-category')->middleware('can:view_sales_reports');
    Route::get('/sales-by-payment-method', [ExportController::class, 'salesByPaymentMethod'])->name('sales-by-payment-method')->middleware('can:view_sales_reports');
    Route::get('/product-returns', [App\Http\Controllers\ExportController::class, 'productReturns'])->name('product-returns')->middleware('can:view_returns');
    Route::get('/shifts', [ExportController::class, 'shifts'])->name('shifts')->middleware('can:view_all_shifts');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/returns/{return}/print', [App\Http\Controllers\ExportController::class, 'printReturn'])->name('returns.print')->middleware('can:view_returns');
});

// POS Routes
Route::middleware(['auth', 'throttle:pos'])->group(function () {
    Route::get('/pos', PosCheckout::class)->name('pos');
    Route::get('/pos/display', App\Livewire\CustomerDisplay::class)->name('pos.display');
});

// Kitchen Display Route
Route::middleware(['auth', 'can:view_kitchen_display'])->group(function () {
    Route::get('/kitchen', App\Livewire\KitchenDisplay::class)->name('kitchen.display');
});

// ─── Payment Gateway Routes ───────────────────────────────────────────────────

// Midtrans Webhook (no auth, CSRF excluded in bootstrap/app.php, signature verified by middleware)
Route::post('/api/webhook/midtrans', App\Http\Controllers\Payment\MidtransWebhookController::class)
    ->middleware(['midtrans.signature', 'throttle:midtrans-webhook'])
    ->name('payment.webhook.midtrans');

// ─── Fonnte (WhatsApp) Webhook ─────────────────────────────────────────────────
// No auth, CSRF excluded in bootstrap/app.php. Fonnte tidak punya signature/HMAC
// sendiri, jadi diverifikasi lewat secret token di URL (VerifyFonnteWebhookSecret).
Route::post('/api/webhook/fonnte/{secret}/device-status', [App\Http\Controllers\Webhook\FonnteWebhookController::class, 'deviceStatus'])
    ->where('secret', '[A-Za-z0-9]{32,64}')
    ->middleware(['fonnte.webhook.secret', 'throttle:fonnte-webhook'])
    ->name('webhook.fonnte.device-status');

Route::post('/api/webhook/fonnte/{secret}/message-status', [App\Http\Controllers\Webhook\FonnteWebhookController::class, 'messageStatus'])
    ->where('secret', '[A-Za-z0-9]{32,64}')
    ->middleware(['fonnte.webhook.secret', 'throttle:fonnte-webhook'])
    ->name('webhook.fonnte.message-status');

// QRIS Status SSE — auth required, polling database status setiap 2 detik
Route::middleware(['auth'])->group(function () {
    Route::get('/api/payment/qris-status/{orderId}', App\Http\Controllers\Payment\QrisStatusSseController::class)
        ->name('payment.qris.sse');
});

// Manual status check (bisa dipanggil kasir maupun layar pelanggan) dengan Rate Limiting (max 30 req/min)
Route::middleware(['throttle:30,1'])->get('/api/payment/qris-check/{orderId}', function (string $orderId) {
    $paymentTx = \App\Models\PaymentTransaction::where('midtrans_order_id', $orderId)->first();

    if (!$paymentTx) {
        return response()->json(['error' => 'Not found'], 404);
    }

    // Jika masih pending, langsung cek ke Midtrans API secara real-time
    if ($paymentTx->isPending()) {
        try {
            $midtransRes = app(\App\Services\MidtransService::class)->getTransactionStatus($orderId);
            if (isset($midtransRes['transaction_status'])) {
                $payload = \App\DTOs\Payment\MidtransWebhookPayload::fromArray($midtransRes);
                app(\App\Actions\Payment\HandleMidtransWebhookAction::class)->execute($payload);
                $paymentTx->refresh();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("qris-check Midtrans fetch failed for [{$orderId}]: " . $e->getMessage());
        }
    }

    // Jika sudah paid, bersihkan cart cache kasir
    if ($paymentTx->isPaid()) {
        $cashierId = $paymentTx->created_by;
        if ($cashierId) {
            $cartData = [
                'cart'          => [],
                'subtotal'      => 0,
                'tax_amount'    => 0,
                'total'         => 0,
                'paid_amount'   => 0,
                'change_amount' => 0,
                'customer_name' => '',
                'qris_order_id' => null,
                'qris_code_url' => null,
                'show_qris'     => false,
                'updated_at'    => now()->timestamp,
            ];
            \Illuminate\Support\Facades\Cache::put('pos_active_cart_' . $cashierId, $cartData, now()->addHours(12));
        }
    }

    return response()->json([
        'status'         => $paymentTx->status->value,
        'transaction_id' => $paymentTx->transaction_id,
        'expires_in'     => $paymentTx->expired_at ? max(0, now()->diffInSeconds($paymentTx->expired_at, false)) : null,
    ]);
})->name('payment.qris.check');

// ─── Self Order: Public Routes (tanpa auth) ────────────────────────────────

Route::prefix('self-order')
    ->name('self-order.')
    ->middleware(['throttle:self-order-page'])
    ->group(function () {
        Route::get('/', App\Livewire\SelfOrder\SelfOrderPage::class)->name('index');
        Route::get('/payment/{token}', App\Livewire\SelfOrder\SelfOrderPayment::class)->name('payment');
        Route::get('/track/{token}', App\Livewire\SelfOrder\SelfOrderTracking::class)->name('tracking');
    });

// Self Order API (tanpa auth, token-based)
Route::prefix('api/self-order')
    ->name('api.self-order.')
    ->group(function () {
        Route::get('/payment-sse/{token}', App\Http\Controllers\SelfOrder\SelfOrderPaymentSseController::class)
            ->name('payment.sse')
            ->middleware('throttle:self-order-api');

        Route::get('/payment-status/{token}', App\Http\Controllers\SelfOrder\SelfOrderPaymentStatusController::class)
            ->name('payment.status')
            ->middleware('throttle:self-order-api');

        Route::get('/stock', App\Http\Controllers\SelfOrder\SelfOrderStockController::class)
            ->name('stock')
            ->middleware('throttle:self-order-stock');
    });

// Struk Publik (tanpa auth, token-based — dibagikan lewat WA/email)
Route::get('/receipt/{token}', App\Livewire\PublicReceipt::class)
    ->name('receipt.show')
    ->middleware('throttle:receipt-page');

// Webhook Midtrans Self Order (CSRF excluded, signature verified)
Route::post('/api/webhook/midtrans/self-order', App\Http\Controllers\Payment\SelfOrderMidtransWebhookController::class)
    ->middleware(['midtrans.signature', 'throttle:midtrans-webhook'])
    ->name('payment.webhook.midtrans.self-order');

// Self Order: Admin Dashboard & Print (auth required)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/self-orders', App\Livewire\Admin\SelfOrderDashboard::class)
        ->name('admin.self-orders.index')
        ->middleware('can:view_self_orders');

    Route::get('/admin/self-orders/{id}/print', function (int $id) {
        $selfOrder = \App\Models\SelfOrder::getForPrint($id);
        abort_if(!$selfOrder, 404);
        return view('print.self-order-receipt', compact('selfOrder'));
    })
        ->name('admin.self-orders.print')
        ->middleware('can:print_self_order_receipt');
});
