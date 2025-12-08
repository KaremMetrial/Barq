<?php

use Illuminate\Support\Facades\Route;
use Modules\WorkingDay\Http\Controllers\WorkingDayController;

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'ability:admin,vendor'])->group(function () {
    Route::apiResource('workingdays', WorkingDayController::class)->names('workingday');
});
