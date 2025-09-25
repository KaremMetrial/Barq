<?php

use Illuminate\Support\Facades\Route;
use Modules\Banner\Http\Controllers\BannerController;

Route::prefix('v1')->group(function () {
    Route::apiResource('banners', BannerController::class)->names('banner');
});
