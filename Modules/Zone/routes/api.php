<?php

use Illuminate\Support\Facades\Route;
use Modules\Zone\Http\Controllers\ZoneController;

Route::prefix('v1')->group(function () {
    Route::apiResource('zones', ZoneController::class)->names('zone');
});
