<?php

use Illuminate\Support\Facades\Route;
use Modules\Couier\Http\Controllers\CouierController;

Route::prefix('v1')->group(function () {
    Route::apiResource('couiers', CouierController::class)->names('couier');
});
