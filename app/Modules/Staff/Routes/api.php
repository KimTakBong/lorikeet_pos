<?php

use App\Modules\Staff\Controllers\StaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Staff Management
    Route::get('/staff', [StaffController::class, 'index']);
    Route::get('/staff/{id}', [StaffController::class, 'show']);
    Route::post('/staff', [StaffController::class, 'store']);
    Route::put('/staff/{id}', [StaffController::class, 'update']);
    Route::delete('/staff/{id}', [StaffController::class, 'destroy']);
    
    // Shifts
    Route::get('/shifts', [StaffController::class, 'shifts']);
    Route::get('/shifts/active', [StaffController::class, 'activeShifts']);
    Route::get('/shifts/{id}/summary', [StaffController::class, 'getShiftSummary']);
    Route::post('/shifts/start', [StaffController::class, 'startShift']);
    Route::post('/shifts/{id}/end', [StaffController::class, 'endShift']);
});
