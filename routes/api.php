<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Dashboard
    Route::get('/v1/dashboard/stats', [\App\Http\Controllers\DashboardController::class, 'stats']);

    // Settings
    Route::get('/settings', [App\Http\Controllers\SettingsController::class, 'index']);
    Route::post('/settings', [App\Http\Controllers\SettingsController::class, 'update']);

    // Products Module
    require __DIR__ . '/../app/Modules/Products/Routes/api.php';
    
    // Inventory Module
    require __DIR__ . '/../app/Modules/Inventory/Routes/api.php';
    
    // Customers Module
    require __DIR__ . '/../app/Modules/Customers/Routes/api.php';
    
    // Campaigns Module
    require __DIR__ . '/../app/Modules/Campaigns/Routes/api.php';
    
    // Expenses Module
    require __DIR__ . '/../app/Modules/Expenses/Routes/api.php';
    
    // Analytics Module
    require __DIR__ . '/../app/Modules/Analytics/Routes/api.php';
    
    // Staff Module
    require __DIR__ . '/../app/Modules/Staff/Routes/api.php';
    
    // Orders Module
    require __DIR__ . '/../app/Modules/Orders/Routes/api.php';
    
    // POS Module
    require __DIR__ . '/../app/Modules/POS/Routes/api.php';
});
