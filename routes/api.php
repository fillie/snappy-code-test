<?php

use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

Route::prefix('stores')->group(function () {
    Route::post('/', [StoreController::class, 'store']);
    Route::get('/nearby', [StoreController::class, 'nearby']);
    Route::get('/deliverable', [StoreController::class, 'deliverable']);
})->middleware('auth:sanctum');
