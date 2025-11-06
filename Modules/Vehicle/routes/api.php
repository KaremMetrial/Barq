<?php

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->middleware(['auth:sanctum'])->group(function () {
        Route::apiResource('vehicles', VehicleController::class);
    });
});
