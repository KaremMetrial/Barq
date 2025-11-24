<?php

use Illuminate\Support\Facades\Route;
use Modules\Couier\Http\Controllers\CouierController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
        Route::prefix('couiers')->group(function () {
            Route::get('stats', [CouierController::class, 'stats']);
        });

        Route::apiResource('couiers', CouierController::class)->names('couier');
    });
});
