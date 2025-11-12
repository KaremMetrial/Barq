<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethod\Http\Controllers\PaymentMethodController;
use Modules\PaymentMethod\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;

Route::prefix('v1')->group(function () {
    Route::prefix('paymentmethods')->group(function () {
        Route::get('/', [PaymentMethodController::class, 'index']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum','ability:admin'])->group(function () {
        Route::apiResource('paymentmethods', AdminPaymentMethodController::class);
    });
});
