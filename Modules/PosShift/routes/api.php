<?php

use Illuminate\Support\Facades\Route;
use Modules\PosShift\Http\Controllers\PosShiftController;

Route::prefix('v1')->group(function () {
    Route::apiResource('posshifts', PosShiftController::class)->names('posshift');
});
