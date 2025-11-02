<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\User\StoreController;
use Modules\Store\Http\Controllers\Admin\StoreController as AdminStoreController;
use Modules\Store\Http\Controllers\Vendor\StoreController as VendorStoreController;
Route::prefix('v1')->group(function () {
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('/', 'index')->name('store.index');
        Route::get('/home', 'home')->name('store.home');
        Route::get('/{id}', 'show')->name('store.show');
    });

    // Vendor
    Route::prefix('vendors')->middleware('auth:vendor')->controller(VendorStoreController::class)->group(function () {
        Route::prefix('store')->group(function () {
            Route::get('/stats', 'stats')->name('vendor.store.stats');
        });
    });

    // Admin
    Route::prefix('admin')->middleware('auth:admin')->group(function () {
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/stats', [AdminStoreController::class, 'stats'])->name('status');
        });
        Route::apiResource('stores', AdminStoreController::class)->names('store');
    });
});
