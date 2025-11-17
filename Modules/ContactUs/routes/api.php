<?php

use Illuminate\Support\Facades\Route;
use Modules\ContactUs\Http\Controllers\ContactUsController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->middleware('auth:admin')->group(function () {
        Route::get('contactuses', [ContactUsController::class,'index']);
    });
    Route::post('contactuses', [ContactUsController::class,'store']);
});
