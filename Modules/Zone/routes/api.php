<?php

use Illuminate\Support\Facades\Route;
use Modules\Zone\Http\Controllers\ZoneController;
use Modules\Zone\Http\Controllers\Admin\ZoneController as AdminZoneController;

Route::prefix('v1')->group(function () {
    Route::prefix('zones')->controller(ZoneController::class)->name('zone.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('zones', AdminZoneController::class)->names('zone');
    });
});
