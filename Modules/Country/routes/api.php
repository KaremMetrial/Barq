<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Http\Controllers\CountryController;

Route::prefix('v1')->group(function () {
    Route::apiResource('countries', CountryController::class)->names('country');
});
