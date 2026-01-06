<?php

use App\Http\Controllers\ExportController;
use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Modifiers;
use App\Livewire\Admin\PaymentSources;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\ProductSalesReport;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\ShiftReport;
use App\Livewire\Admin\Shifts;
use App\Livewire\Admin\TransactionHistory;
use App\Livewire\Admin\Users;
use App\Livewire\PosCheckout;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

// Login with username
Route::get('/login', function () {
    return view('auth.login');
})->name('login')->middleware('guest');

Route::post('/login', function () {
    $credentials = [
        'username' => request('username'),
        'password' => request('password'),
    ];
    
    if (auth()->attempt($credentials)) {
        request()->session()->regenerate();
        return redirect()->intended('/admin');
    }
    
    return back()->withErrors(['username' => 'Username atau password salah'])->withInput();
})->name('login.post');

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

// Admin Routes
Route::middleware(['auth', 'throttle:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    
    // Master Data
    Route::get('/categories', Categories::class)->name('categories.index');
    Route::get('/products', Products::class)->name('products.index');
    Route::get('/products/{product}', App\Livewire\Admin\ProductDetail::class)->name('products.show');
    Route::get('/modifiers', Modifiers::class)->name('modifiers.index');
    Route::get('/payment-sources', PaymentSources::class)->name('payment-sources.index');
    Route::get('/service-areas', App\Livewire\Admin\ServiceAreas::class)->name('service-areas.index');
    
    // Transactions & Shifts
    Route::get('/shifts', Shifts::class)->name('shifts.index');
    Route::get('/returns', App\Livewire\Admin\Returns::class)->name('returns');
    
    // Reports
    Route::get('/reports/transactions', TransactionHistory::class)->name('reports.transactions');
    Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
    Route::get('/reports/products', ProductSalesReport::class)->name('reports.products');
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
    Route::get('/transaction/{transaction}', [App\Http\Controllers\PrintController::class, 'transactionSingle'])->name('transaction.single');
    Route::get('/shifts/table', [App\Http\Controllers\PrintController::class, 'shiftsTable'])->name('shifts.table');
    Route::get('/shift/{shift}', [App\Http\Controllers\PrintController::class, 'shiftDetail'])->name('shift.detail');
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
