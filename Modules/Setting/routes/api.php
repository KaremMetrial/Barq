<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

Route::prefix('v1')->group(function () {
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('setting.index');
        Route::prefix('admin')->middleware('auth:admin')->group(function () {
            Route::put('/{setting}', [SettingController::class, 'update'])->name('setting.update');
        });
    });
});
