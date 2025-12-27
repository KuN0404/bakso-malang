<?php

use App\Livewire\Admin\Categories;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Modifiers;
use App\Livewire\Admin\PaymentSources;
use App\Livewire\Admin\Products;
use App\Livewire\Admin\Roles;
use App\Livewire\Admin\SalesReport;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\ShiftReport;
use App\Livewire\Admin\Shifts;
use App\Livewire\Admin\Transactions;
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
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    
    // Master Data
    Route::get('/categories', Categories::class)->name('categories.index');
    Route::get('/products', Products::class)->name('products.index');
    Route::get('/modifiers', Modifiers::class)->name('modifiers.index');
    Route::get('/payment-sources', PaymentSources::class)->name('payment-sources.index');
    
    // Transactions
    Route::get('/transactions', Transactions::class)->name('transactions.index');
    Route::get('/shifts', Shifts::class)->name('shifts.index');
    Route::get('/returns', App\Livewire\Admin\Returns::class)->name('returns');
    
    // Reports
    Route::get('/reports/sales', SalesReport::class)->name('reports.sales');
    Route::get('/reports/shifts', ShiftReport::class)->name('reports.shifts');
    
    // Settings
    Route::get('/users', Users::class)->name('users.index');
    Route::get('/roles', Roles::class)->name('roles.index');
    Route::get('/settings', Settings::class)->name('settings.index');
});

// POS Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/pos', PosCheckout::class)->name('pos');
});
