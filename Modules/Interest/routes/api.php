<?php

use Illuminate\Support\Facades\Route;
use Modules\Interest\Http\Controllers\InterestController;

Route::prefix('v1')->group(function () {
    Route::apiResource('interests', InterestController::class)->names('interest');
});
