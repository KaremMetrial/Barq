<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;

Route::prefix('v1')->group(function () {
    Route::apiResource('admins', AdminController::class)->names('admin');
      Route::prefix('admins')->controller(AdminController::class)->group(function () {
        Route::post('login', 'login');

        Route::middleware('auth:admin')->group(function () {
            Route::post('logout', 'logout');

            Route::post('update-password', 'updatePassword');
        });
    });
});
