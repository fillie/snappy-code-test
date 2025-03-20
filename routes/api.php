<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('stores')->group(function () {
    Route::post('/', [StoreController::class, 'store']);
    // Route::get('/nearby', 'StoreController@nearby');
    // Route::get('/deliverable', 'StoreController@deliverable');
})->middleware('auth:sanctum');
