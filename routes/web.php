<?php

use App\Http\Controllers\Auth\WebLoginController;
use App\Http\Controllers\Auth\WebRegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\GoldReceiptController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SyncStatusController;
use Illuminate\Support\Facades\Route;

Route::get('public', function () {
    return redirect()->route('login');
});

Route::get('public/index.php', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [WebLoginController::class, 'showLoginForm'])->name('login');
    Route::post('login', [WebLoginController::class, 'login'])->name('login.submit');

    Route::get('register', [WebRegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('register', [WebRegisterController::class, 'register'])->name('register.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.alt');

    // Invoices
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/export/csv', [InvoiceController::class, 'export'])->name('invoices.export');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');
    Route::get('/invoices/{invoice}/edit', [InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{invoice}', [InvoiceController::class, 'update'])->name('invoices.update');
    Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

    // Gold Receipts (Receive Vouchers)
    Route::get('/gold-receipts', [GoldReceiptController::class, 'index'])->name('gold-receipts.index');
    Route::get('/gold-receipts/create', [GoldReceiptController::class, 'create'])->name('gold-receipts.create');
    Route::post('/gold-receipts', [GoldReceiptController::class, 'store'])->name('gold-receipts.store');
    Route::get('/gold-receipts/{goldReceipt}/print', [GoldReceiptController::class, 'print'])->name('gold-receipts.print');
    Route::get('/gold-receipts/{goldReceipt}/edit', [GoldReceiptController::class, 'edit'])->name('gold-receipts.edit');
    Route::put('/gold-receipts/{goldReceipt}', [GoldReceiptController::class, 'update'])->name('gold-receipts.update');
    Route::delete('/gold-receipts/{goldReceipt}', [GoldReceiptController::class, 'destroy'])->name('gold-receipts.destroy');
    Route::get('/gold-receipts/{goldReceipt}', [GoldReceiptController::class, 'show'])->name('gold-receipts.show');

    // Customers
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');
    Route::get('/customers/{customer}/last-balance', [CustomerController::class, 'lastBalance'])->name('customers.last-balance');
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->name('customers.destroy');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    // Ledger
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');

    // Inventory
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/update', [InventoryController::class, 'update'])->name('inventory.update');

    // Reports
    Route::get('/reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
    Route::get('/reports/customer', [ReportController::class, 'customer'])->name('reports.customer');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // Sync status page
    Route::get('/sync-status', [SyncStatusController::class, 'index'])->name('sync.status');

    Route::post('logout', [WebLoginController::class, 'logout'])->name('logout');

    // Customer balance API endpoint
Route::get('/customers/{id}/last-balance', [InvoiceController::class, 'customerLastBalance']);
});
