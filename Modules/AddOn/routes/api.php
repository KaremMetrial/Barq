<?php

use Illuminate\Support\Facades\Route;
use Modules\AddOn\Http\Controllers\AddOnController;

Route::prefix('v1')->group(function () {
    Route::apiResource('addons', AddOnController::class)->names('addon');
});
