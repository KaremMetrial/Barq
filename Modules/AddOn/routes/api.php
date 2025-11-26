<?php

use Illuminate\Support\Facades\Route;
use Modules\AddOn\Http\Controllers\AddOnController;

Route::prefix('v1')->middleware(['auth:sanctum','ability:admin,vendor'])->group(function () {
    Route::apiResource('admin/addons', AddOnController::class)->names('addon');
});
