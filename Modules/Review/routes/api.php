<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\ReviewController;
use Modules\Review\Http\Controllers\RatingKeyController;

Route::middleware(['auth:sanctum', 'ability:admin,vendor,user'])->prefix('v1')->group(function () {
    Route::get('orders/{orderId}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('stores/{store}/reviews', [ReviewController::class, 'storeIndex'])->name('reviews.storeIndex');
    Route::apiResource('reviews', ReviewController::class)->names('review');
    Route::apiResource('rating-keys', RatingKeyController::class)->names('rating-keys');
});

Route::prefix('v1')->prefix('admin')->middleware(['auth:sanctum', 'ability:admin,vendor,user'])->group(function () {
    Route::apiResource('reviews', ReviewController::class)->names('admin.review');
});
