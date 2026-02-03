<?php

use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

Route::prefix('v1')->group(function () {
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('setting.index');
    });
});
Route::prefix('v1/admin')->middleware('auth:admin')->group(function () {
    Route::get('/settings', [SettingController::class, 'index'])->name('admin.setting.index');
    Route::put('/settings/update', [SettingController::class, 'update'])->name('admin.setting.update');
});
