<?php

use Illuminate\Support\Facades\Route;
use Modules\StoreSetting\Http\Controllers\StoreSettingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('storesettings', StoreSettingController::class)->names('storesetting');
});
