<?php

use Illuminate\Support\Facades\Route;
use Modules\Category\Http\Controllers\CategoryController;
use Modules\Category\Http\Controllers\Admin\CategoryController as AdminCategoryController;
Route::prefix('v1')->group(function () {
    Route::prefix('categories')->controller(CategoryController::class)->name('category.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{id}', 'show')->name('show');
    });

    // Admin
    Route::prefix('admin')->middleware('auth:sanctum', 'ability:admin,vendor')->name('admin.')->group(function () {
        Route::apiResource('categories', AdminCategoryController::class)->names('category');
    });
});
