<?php

use App\Modules\Orders\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Orders
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    
    // Refunds
    Route::post('/orders/{id}/refund', [OrderController::class, 'refund']);
    Route::get('/orders/{id}/refund', [OrderController::class, 'getRefund']);
    Route::post('/orders/{id}/send-receipt', [OrderController::class, 'sendReceipt']);
});
