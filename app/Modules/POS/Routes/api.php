<?php

use App\Modules\POS\Controllers\POSController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // POS Transactions
    Route::post('/orders', [POSController::class, 'createOrder']);
    Route::get('/orders/{order}', [POSController::class, 'getOrder']);
    
    // Products
    Route::get('/products', [POSController::class, 'getProducts']);
    
    // Customers
    Route::get('/customers', [POSController::class, 'getCustomers']);
    Route::post('/customers', [POSController::class, 'createCustomer']);
    
    // Payment Methods
    Route::get('/payment-methods', [POSController::class, 'getPaymentMethods']);
    
    // Shifts
    Route::post('/shifts/start', [POSController::class, 'startShift']);
    Route::post('/shifts/end', [POSController::class, 'endShift']);
});
