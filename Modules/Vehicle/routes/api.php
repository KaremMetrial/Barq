<?php

use Illuminate\Support\Facades\Route;
use Modules\Vehicle\Http\Controllers\VehicleController;

Route::prefix('v1')->group(function () {
    Route::apiResource('vehicles', VehicleController::class)->names('vehicle');
});
