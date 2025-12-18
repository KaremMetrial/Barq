<?php

use Illuminate\Support\Facades\Route;
use Modules\Coupon\Http\Controllers\CouponController;
use Modules\Coupon\Http\Controllers\Admin\CouponController as AdminCouponController;
use Modules\Coupon\Http\Controllers\Vendor\CouponController as VendorCouponController;

Route::prefix('v1')->group(function () {
    Route::prefix('coupons')->controller(CouponController::class)->name('coupon.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });
    Route::prefix('admin')->middleware(['auth:sanctum','ability:admin,vendor'])->name('admin.')->group(function () {
        Route::apiResource('coupons', AdminCouponController::class)->names('coupon');
    });
    Route::prefix('vendor')->middleware('auth:vendor')->name('vendor.')->group(function () {
        Route::apiResource('coupons', VendorCouponController::class)->names('coupon');
    });
});
