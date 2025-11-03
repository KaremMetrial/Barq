<?php

use Illuminate\Support\Facades\Route;
use Modules\PaymentMethod\Http\Controllers\PaymentMethodController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('paymentmethods', PaymentMethodController::class)->names('paymentmethod');
});
