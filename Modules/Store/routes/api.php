<?php

use Illuminate\Support\Facades\Route;
use Modules\Store\Http\Controllers\StoreController;

Route::prefix('v1')->group(function () {
    Route::apiResource('stores', StoreController::class)->names('store');
});
