<?php

use Illuminate\Support\Facades\Route;
use Modules\Otp\Http\Controllers\OtpController;

Route::prefix('v1')->group(function () {
    Route::controller(OtpController::class)->group(function () {
        Route::post('send-otp', 'sendOtp')->name('otp.send');
        Route::post('verify-otp', 'verifyOtp')->name('otp.verify');

        // Register
        Route::post('register', 'register')->name('register');
    });
    Route::prefix('admin')->group(function () {
        Route::controller(OtpController::class)->group(function () {
            Route::post('send-otp', 'sendOtp')->name('otp.send');
            Route::post('verify-otp', 'verifyOtp')->name('otp.verify');
        });
    });
});
