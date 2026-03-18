<?php

use App\Modules\POS\Controllers\POSController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // POS Transactions (order creation only - detail via Orders module)
    Route::post('/orders', [POSController::class, 'createOrder']);
    
    // Customers
    Route::get('/customers', [POSController::class, 'getCustomers']);
    Route::post('/customers', [POSController::class, 'createCustomer']);
    
    // Payment Methods
    Route::get('/payment-methods', [POSController::class, 'getPaymentMethods']);
});
