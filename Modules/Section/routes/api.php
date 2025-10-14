<?php

use Illuminate\Support\Facades\Route;
use Modules\Section\Http\Controllers\SectionController;
use Modules\Section\Http\Controllers\Admin\SectionController as AdminSectionController;

Route::prefix('v1')->group(function () {
    Route::prefix('sections')->controller(SectionController::class)->name('section.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });
    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('sections', AdminSectionController::class)->names('section');
    });
});
