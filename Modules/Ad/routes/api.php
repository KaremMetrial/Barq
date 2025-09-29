<?php

use Illuminate\Support\Facades\Route;
use Modules\Ad\Http\Controllers\AdController;

Route::prefix('v1')->group(function () {
    Route::apiResource('ads', AdController::class)->names('ad');
});
