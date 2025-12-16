<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\ReviewController;

Route::middleware(['auth:sanctum', 'ability:admin,vendor'])->prefix('v1')->group(function () {
    Route::get('orders/{orderId}/reviews', [ReviewController::class, 'index'])->name('reviews.index');
    Route::get('stores/{storeId}/reviews', [ReviewController::class, 'storeIndex'])->name('reviews.storeIndex');
    Route::apiResource('reviews', ReviewController::class)->names('review');
});
