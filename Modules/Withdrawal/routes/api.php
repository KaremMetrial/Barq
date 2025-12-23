<?php

use Illuminate\Support\Facades\Route;
use Modules\Withdrawal\Http\Controllers\WithdrawalController;

Route::middleware(['auth:sanctum','ability:admin,vendor,courier'])->prefix('v1/admin')->group(function () {
    Route::apiResource('withdrawals', WithdrawalController::class)->names('withdrawal');
});

