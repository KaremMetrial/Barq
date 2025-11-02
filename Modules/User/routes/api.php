<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;
use Modules\User\Http\Controllers\LoyaltyController;
use Modules\User\Http\Controllers\WalletController;

Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('user');

    Route::post('register', [UserController::class, 'register'])->name('register');

    // Loyalty routes (protected by user auth)
    Route::middleware('auth:user')->prefix('loyalty')->group(function () {
        Route::get('balance', [LoyaltyController::class, 'balance'])->name('loyalty.balance');
        Route::get('history', [LoyaltyController::class, 'history'])->name('loyalty.history');
        Route::post('validate-redemption', [LoyaltyController::class, 'validateRedemption'])->name('loyalty.validate-redemption');
        Route::post('redeem', [LoyaltyController::class, 'redeem'])->name('loyalty.redeem');
        Route::post('calculate-redemption', [LoyaltyController::class, 'calculateRedemption'])->name('loyalty.calculate-redemption');
    });

    // Wallet route
    Route::get('wallet', [WalletController::class, 'index'])->middleware('auth:user')->name('wallet.index');
});
