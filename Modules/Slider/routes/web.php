<?php

use Illuminate\Support\Facades\Route;
use Modules\Slider\Http\Controllers\SliderController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('sliders', SliderController::class)->names('slider');
});
