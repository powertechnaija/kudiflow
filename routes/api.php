<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test', function(){ return response()->json('Hello World'); });


// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // User & Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    // Inside auth middleware group
    Route::apiResource('users', UserController::class)->middleware('role:admin|manager');

    // Setup
    Route::post('/setup/opening-balance', [SetupController::class, 'openingBalance']);

    // Inventory Management
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::get('/products/{product}/history', [ProductController::class, 'history']);
    Route::put('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/inventory/purchase', [PurchaseController::class, 'store']); // Procurement
    
    // Mobile Scanner Endpoint
    Route::post('/products/find-barcode', [ProductController::class, 'findByBarcode']);

    // Sales & Operations
    Route::post('/orders', [OrderController::class, 'store']); // POS Sale
    Route::post('/returns', [ReturnController::class, 'store']); // Returns

    // Expenses
    Route::post('/petty-cash', [PettyCashController::class, 'store']);
    //customer management
    Route::apiResource('customers', CustomerController::class);

    // Accounting & Reporting (Role Protected: Admin/Manager only)
    Route::middleware(['role:admin|manager'])->group(function () {
        
        // Chart of Accounts
        Route::get('/accounting/accounts', [ChartOfAccountController::class, 'index']);
        Route::post('/accounting/accounts', [ChartOfAccountController::class, 'store']); // Add custom accounts
        
        // General Ledger
        Route::get('/accounting/ledger', [LedgerController::class, 'index']);

        // Reports
        Route::get('/reports/profit-loss', [ReportController::class, 'profitAndLoss']);
        Route::get('/reports/balance-sheet', [ReportController::class, 'balanceSheet']);
    });

});