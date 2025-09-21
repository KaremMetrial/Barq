<?php

use Illuminate\Support\Facades\Route;
use Modules\StoreSetting\Http\Controllers\StoreSettingController;

Route::prefix('v1')->group(function () {
    Route::apiResource('storesettings', StoreSettingController::class)->names('storesetting');
});
