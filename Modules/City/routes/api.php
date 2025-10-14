<?php

use Illuminate\Support\Facades\Route;
use Modules\City\Http\Controllers\CityController;
use Modules\City\Http\Controllers\Admin\CityController as adminCityController;

Route::prefix('v1')->group(function () {
    Route::prefix('cities')->controller(CityController::class)->name('city.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('cities', adminCityController::class)->names('city');
    });
});
