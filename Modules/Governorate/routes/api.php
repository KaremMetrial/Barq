<?php

use Illuminate\Support\Facades\Route;
use Modules\Governorate\Http\Controllers\GovernorateController;

Route::prefix('v1')->group(function () {
    Route::apiResource('governorates', GovernorateController::class)->names('governorate');
});
