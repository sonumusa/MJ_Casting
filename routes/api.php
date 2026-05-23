<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    InvoiceController,
    CustomerController,
    DashboardController,
    InventoryController,
    ReportController,
};
use App\Http\Controllers\SyncController;

Route::get('v1/ping', function () {
    return response()->json(['ok' => true]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('v1')->name('api.v1.')->group(function () {
        // Dashboard
        Route::get('dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        Route::post('invoices/calculate', [InvoiceController::class, 'calculate'])->name('invoices.calculate');
        Route::post('invoices/recalculate-all', [InvoiceController::class, 'recalculateAll'])->name('invoices.recalculate-all');
        Route::get('invoices/changes', [InvoiceController::class, 'getChanges'])->name('invoices.changes');

        // Offline sync
        Route::post('sync/batch', [SyncController::class, 'processBatch'])->name('sync.batch');

        // Customers
        Route::apiResource('customers', CustomerController::class);
        Route::get('customers/{customer}/balance', [CustomerController::class, 'balance'])->name('customers.balance');
        Route::get('customers/{customer}/ledger', [CustomerController::class, 'ledger'])->name('customers.ledger');

        // Inventory
        Route::get('inventory', [InventoryController::class, 'show'])->name('inventory.show');
        Route::post('inventory', [InventoryController::class, 'update'])->name('inventory.update');

        // Reports
        Route::get('reports/daily', [ReportController::class, 'daily'])->name('reports.daily');
        Route::get('reports/customer', [ReportController::class, 'customer'])->name('reports.customer');
    });
});

// Public routes
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login']);
Route::post('/register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
