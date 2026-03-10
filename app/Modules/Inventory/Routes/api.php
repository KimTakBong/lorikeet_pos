<?php

use App\Modules\Inventory\Controllers\InventoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Stock Levels
    Route::get('/inventory', [InventoryController::class, 'index']);
    Route::get('/inventory/{productId}', [InventoryController::class, 'show']);
    
    // Stock Movements
    Route::get('/inventory/movements', [InventoryController::class, 'movements']);
    Route::get('/inventory/products/{productId}/movements', [InventoryController::class, 'productMovements']);
    
    // Restock
    Route::post('/inventory/restock', [InventoryController::class, 'restock']);
    
    // Stock Adjustment
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust']);
    
    // Suppliers
    Route::get('/suppliers', [InventoryController::class, 'suppliers']);
});
