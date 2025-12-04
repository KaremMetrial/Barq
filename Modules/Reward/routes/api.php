<?php

use Illuminate\Support\Facades\Route;
use Modules\Reward\Http\Controllers\AdminRewardController;
use Modules\Reward\Http\Controllers\RewardController;

// Admin routes
Route::middleware(['auth:sanctum', 'ability:admin'])->prefix('v1/admin')->group(function () {
    Route::apiResource('rewards', AdminRewardController::class);
});

// User routes
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('rewards', [RewardController::class, 'index']);
    Route::get('rewards/{id}', [RewardController::class, 'show']);
    Route::post('rewards/{id}/redeem', [RewardController::class, 'redeem']);
    Route::get('my-redemptions', [RewardController::class, 'myRedemptions']);
});
