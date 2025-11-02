<?php

use Illuminate\Support\Facades\Route;
use Modules\Language\Http\Controllers\LanguageController;
use Modules\Language\Http\Controllers\Admin\LanguageController as AdminLanguageController;

Route::prefix('v1')->group(function () {
    Route::prefix('languages')->controller(LanguageController::class)->group(function() {
        Route::get('/', 'index');
    });

    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('languages', AdminLanguageController::class)->names('language');
    });
});
