<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

Route::prefix('v1')->group(function () {
    Route::apiResource('users', UserController::class)->names('user');

    Route::post('register', [UserController::class, 'register'])->name('register');
});
