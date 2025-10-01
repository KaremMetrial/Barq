<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\StoreController;

Route::prefix('v1')->group(function () {
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('/home', 'home')->name('store.home');
    });
    Route::apiResource('stores', StoreController::class)->names('store');
});
