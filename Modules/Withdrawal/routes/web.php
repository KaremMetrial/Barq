<?php

use Illuminate\Support\Facades\Route;
use Modules\Withdrawal\Http\Controllers\WithdrawalController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('withdrawals', WithdrawalController::class)->names('withdrawal');
});
