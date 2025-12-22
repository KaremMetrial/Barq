<?php

use Illuminate\Support\Facades\Route;
use Modules\Banner\Http\Controllers\BannerController;

Route::prefix('v1/admin')->middleware(['auth:admin'])->group(function () {
    Route::apiResource('banners', BannerController::class)->names('banner');
});
Route::prefix('v1')->group(function () {
    Route::get('banners', [BannerController::class, 'getindex'])->name('banners.index');
});
