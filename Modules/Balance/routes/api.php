<?php

use Illuminate\Support\Facades\Route;
use Modules\Balance\Http\Controllers\BalanceController;

Route::prefix('v1')->group(function () {
    Route::apiResource('balances', BalanceController::class)->names('balance');
});
