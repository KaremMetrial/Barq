<?php

use Illuminate\Support\Facades\Route;
use Modules\Compaign\Http\Controllers\CompaignController;
use Modules\Compaign\Http\Controllers\StoreCompaignController;

Route::prefix('v1')->group(function () {
    Route::get('compaigns/{id}/dashboard', [StoreCompaignController::class, 'dashboard']);

    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
        Route::get('compaigns/{id}/participants', [CompaignController::class, 'participants']);
        Route::apiResource('compaigns', CompaignController::class)->names('compaign');
    });
});
