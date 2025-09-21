<?php

use Illuminate\Support\Facades\Route;
use Modules\StoreSetting\Http\Controllers\StoreSettingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('storesettings', StoreSettingController::class)->names('storesetting');
});
