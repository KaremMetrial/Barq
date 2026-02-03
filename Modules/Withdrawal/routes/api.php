<?php

use Illuminate\Support\Facades\Route;
use Modules\Withdrawal\Http\Controllers\WithdrawalController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('withdrawals', WithdrawalController::class)->names('withdrawal');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::post('withdrawals', [WithdrawalController::class, 'store'])->name('withdrawal.store');
});

