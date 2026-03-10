<?php

use App\Modules\Analytics\Controllers\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Dashboard
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
    
    // Sales Trend
    Route::get('/analytics/sales-trend', [AnalyticsController::class, 'salesTrend']);
    
    // Top Products
    Route::get('/analytics/top-products', [AnalyticsController::class, 'topProducts']);
    
    // Payment Breakdown
    Route::get('/analytics/payment-breakdown', [AnalyticsController::class, 'paymentBreakdown']);
});
