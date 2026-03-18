<?php

use Illuminate\Support\Facades\Route;

// Front page - redirect to login
Route::get('/', function () {
    return redirect('/login');
});

// Catch-all route for Vue Router - must be last, exclude api/ prefix
Route::get('/{any}', function () {
    return view('app');
})->where('any', '^(?!api/).*');
