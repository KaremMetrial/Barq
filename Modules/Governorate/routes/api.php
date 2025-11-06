<?php

use Illuminate\Support\Facades\Route;
use Modules\Governorate\Http\Controllers\GovernorateController;

Route::prefix('v1')->group(function () {
    Route::prefix('governorates')->group(function () {
        Route::get('/', [GovernorateController::class, 'index']);
        Route::get('/{id}', [GovernorateController::class, 'show']);
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'abilities:admin'])->group(function () {
        Route::apiResource('governorates', GovernorateController::class)->names('governorate');
    });
});

