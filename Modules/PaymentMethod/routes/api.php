<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethod\Http\Controllers\PaymentMethodController;

Route::prefix('v1')->group(function () {

    Route::apiResource('paymentmethods', PaymentMethodController::class);
    // Public route for getting active payment methods
    Route::get('payment-methods/active', [PaymentMethodController::class, 'getActive'])->name('paymentmethod.active');
});
