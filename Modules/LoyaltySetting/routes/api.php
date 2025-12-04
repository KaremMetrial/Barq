<?php

use Illuminate\Support\Facades\Route;
use Modules\LoyaltySetting\Http\Controllers\LoyaltySettingController;

Route::middleware(['auth:sanctum','ability:admin'])->prefix('v1/admin')->group(function () {
    Route::apiResource('loyaltysettings', LoyaltySettingController::class)->names('loyaltysetting');
});
