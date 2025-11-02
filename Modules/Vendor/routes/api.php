<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;
use Modules\Vendor\Http\Controllers\Admin\VendorController as AdminVendorController;

Route::prefix('v1')->group(function () {
    Route::apiResource('vendors', VendorController::class)->names('vendor');

    Route::prefix('vendors')->controller(VendorController::class)->group(function () {
        Route::post('login', 'login');
        Route::middleware('auth:vendor')->group(function () {
            Route::post('logout', 'logout');

            Route::post('update-password', 'updatePassword');
        });
    });

    Route::prefix('admin')->name('admin')->group(function () {
        Route::apiResource('vendors', AdminVendorController::class)->names('vendor');
    });
});
