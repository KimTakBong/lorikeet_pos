<?php

use App\Modules\Expenses\Controllers\ExpenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    // Expenses
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::get('/expenses/{id}', [ExpenseController::class, 'show']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
    Route::put('/expenses/{id}', [ExpenseController::class, 'update']);
    Route::delete('/expenses/{id}', [ExpenseController::class, 'destroy']);
    
    // Expense Categories
    Route::get('/expense-categories', [ExpenseController::class, 'categories']);
    
    // Summary
    Route::get('/expenses-summary', [ExpenseController::class, 'summary']);
});
