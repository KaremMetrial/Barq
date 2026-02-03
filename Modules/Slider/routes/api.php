<?php

use Illuminate\Support\Facades\Route;
use Modules\Slider\Http\Controllers\SliderController;

Route::prefix('v1/admin')->middleware(['auth:admin'])->group(function () {
    Route::apiResource('sliders', SliderController::class)->names('slider');
});
Route::prefix('v1')->group(function () {
    Route::get('sliders', [SliderController::class, 'getindex'])->name('sliders.index');
});