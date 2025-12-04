<?php

use Illuminate\Support\Facades\Route;
use Modules\Review\Http\Controllers\ReviewController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('orders/{orderId}/reviews', [ReviewController::class, 'index'])->name('reviews.index');

    Route::apiResource('reviews', ReviewController::class)->names('review');
});
