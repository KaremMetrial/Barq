<?php

use Illuminate\Support\Facades\Route;
use Modules\Option\Http\Controllers\OptionController;
use Modules\Option\Http\Controllers\Admin\OptionController as AdminOptionController;

Route::prefix('v1')->group(function () {
    Route::prefix('options')->controller(OptionController::class)->name('option.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });
    // Admin
    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('options', AdminOptionController::class)->names('option');
    });

});
