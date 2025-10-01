<?php

use Illuminate\Support\Facades\Route;
use Modules\Cart\Http\Controllers\CartController;

Route::prefix('v1')->group(function () {
    Route::middleware('auth:user')->prefix('carts')->controller(CartController::class)->group(function () {
        Route::get('/share/{id}', 'shareCart')->name('cart.share');
        Route::post('/join/{cart_key}', 'joinCart')->name('cart.join');
    });
    Route::apiResource('carts', CartController::class)->names('cart');
});
