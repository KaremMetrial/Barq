<?php

use Illuminate\Support\Facades\Route;
use Modules\City\Http\Controllers\CityController;

Route::prefix('v1')->group(function () {
    Route::apiResource('cities', CityController::class)->names('city');
});
