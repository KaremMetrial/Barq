<?php

use Illuminate\Support\Facades\Route;
use Modules\Country\Http\Controllers\CountryController;
use Modules\Country\Http\Controllers\Admin\CountryController as AdminCountryController;

Route::prefix('v1')->group(function () {
    Route::prefix('countries')->name('country.')->controller(CountryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });
    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('countries', AdminCountryController::class)->names('country');
    });
});
