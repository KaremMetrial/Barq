<?php

use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\Admin\RoleController as AdminRoleController;

Route::prefix('v1')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::apiResource('roles', AdminRoleController::class);
    });
});
