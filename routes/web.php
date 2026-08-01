<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Components;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Ingredients;
use App\Livewire\Admin\InventoryReport;
use App\Livewire\Admin\Modifiers;
use App\Livewire\Admin\PaymentSources;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\ProductSalesReport;
use App\Livewire\Admin\Productions;
use App\Livewire\Admin\Purchases;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\HppCalculator;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\ShiftReport;
use App\Livewire\Admin\Shifts;
use App\Livewire\Admin\TransactionHistory;
use App\Livewire\Admin\Users;
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
Route::middleware(['auth', 'throttle:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/menu', App\Livewire\Admin\MenuCatalog::class)->name('menu.index');
    Route::get('/hpp-calculator', HppCalculator::class)->name('hpp-calculator.index');
    
    // Master Data
    Route::get('/categories', Categories::class)->name('categories.index');
    Route::get('/products', Products::class)->name('products.index');
    Route::get('/products/{product}', App\Livewire\Admin\ProductDetail::class)->name('products.show');
    Route::get('/modifiers', Modifiers::class)->name('modifiers.index');
    Route::get('/payment-sources', PaymentSources::class)->name('payment-sources.index');
    Route::get('/service-areas', App\Livewire\Admin\ServiceAreas::class)->name('service-areas.index');
    
    // Inventory & Production
    Route::get('/ingredients', Ingredients::class)->name('ingredients.index');
    Route::get('/components', Components::class)->name('components.index');
    Route::get('/purchases', Purchases::class)->name('purchases.index');
    Route::get('/productions', Productions::class)->name('productions.index');
    
    // Transactions & Shifts
    Route::get('/shifts', Shifts::class)->name('shifts.index');
    Route::get('/returns', App\Livewire\Admin\Returns::class)->name('returns');
    
    // Reports
    Route::get('/reports/transactions', TransactionHistory::class)->name('reports.transactions');
    Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
    Route::get('/reports/products', ProductSalesReport::class)->name('reports.products');
    Route::get('/reports/inventory', InventoryReport::class)->name('reports.inventory');
    Route::get('/reports/shifts', ShiftReport::class)->name('reports.shifts');
    
    // Settings
    Route::get('/users', Users::class)->name('users.index');
    Route::get('/roles', Roles::class)->name('roles.index');
    Route::get('/settings', Settings::class)->name('settings.index');
});

// Print Routes
Route::middleware(['auth'])->prefix('print')->name('print.')->group(function () {
    Route::get('/transactions/table', [App\Http\Controllers\PrintController::class, 'transactionsTable'])->name('transactions.table');
    Route::get('/transactions/detail', [App\Http\Controllers\PrintController::class, 'transactionsDetail'])->name('transactions.detail');
    Route::get('/returns-report', [App\Http\Controllers\PrintController::class, 'returnsReport'])->name('returns-report');
    Route::get('/return-detail/{id}', [App\Http\Controllers\PrintController::class, 'returnDetail'])->name('return-detail');
    Route::get('/sales-report', [App\Http\Controllers\PrintController::class, 'salesReport'])->name('sales-report');
    Route::get('/inventory-report', [App\Http\Controllers\PrintController::class, 'inventoryReport'])->name('inventory-report');
    Route::get('/transaction/{transaction}', [App\Http\Controllers\PrintController::class, 'transactionSingle'])->name('transaction.single');
    Route::get('/shifts/table', [App\Http\Controllers\PrintController::class, 'shiftsTable'])->name('shifts.table');
    Route::get('/shift/{shift}', [App\Http\Controllers\PrintController::class, 'shiftDetail'])->name('shift.detail');
    Route::get('/shift/{shift}/custom', [App\Http\Controllers\PrintController::class, 'shiftCustom'])->name('shift.custom');
});

// Export Routes (separate for streaming)
Route::middleware(['auth', 'throttle:5,1'])->name('export.')->prefix('export')->group(function () {
    Route::get('/transactions', [ExportController::class, 'transactions'])->name('transactions');
    Route::get('/transactions-detail', [ExportController::class, 'transactionsDetail'])->name('transactions.detail');
    Route::get('/product-sales', [ExportController::class, 'productSales'])->name('product-sales');
    Route::get('/sales-by-category', [ExportController::class, 'salesByCategory'])->name('sales-by-category');
    Route::get('/sales-by-payment-method', [ExportController::class, 'salesByPaymentMethod'])->name('sales-by-payment-method');
    Route::get('/product-returns', [App\Http\Controllers\ExportController::class, 'productReturns'])->name('product-returns');
    Route::get('/shifts', [ExportController::class, 'shifts'])->name('shifts');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/returns/{return}/print', [App\Http\Controllers\ExportController::class, 'printReturn'])->name('returns.print');
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
    ->middleware('midtrans.signature')
    ->name('payment.webhook.midtrans');

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

