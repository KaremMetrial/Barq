<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;
use Modules\Vendor\Http\Controllers\Admin\VendorController as AdminVendorController;
use Modules\Vendor\Http\Controllers\Vendor\VendorReportController;

Route::prefix('v1')->group(function () {
    Route::apiResource('vendors', VendorController::class)->names('vendor');

    Route::prefix('vendors')->controller(VendorController::class)->group(function () {
        Route::post('login', 'login');
        Route::middleware('auth:vendor')->group(function () {
            Route::post('logout', 'logout');

            Route::post('update-password', 'updatePassword');

            Route::get('profile', 'profile');
        });
    });

    // Vendor Report Endpoints
    Route::prefix('vendor')->middleware('auth:vendor')->group(function () {
        Route::get('reports', [VendorReportController::class, 'getVendorReports']);
    });

    Route::prefix('admin')->name('admin')->group(function () {
        Route::apiResource('vendors', AdminVendorController::class)->names('vendor');
    });
});
