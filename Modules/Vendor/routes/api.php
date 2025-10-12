<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;

Route::prefix('v1')->group(function () {
    Route::apiResource('vendors', VendorController::class)->names('vendor');

    Route::prefix('vendors')->controller(VendorController::class)->group(function () {
        Route::post('login', 'login');

        Route::middleware('auth:vendor')->group(function () {
            Route::post('logout', 'logout');

            Route::post('update-password', 'updatePassword');
        });
    });
});
