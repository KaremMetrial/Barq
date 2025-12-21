<?php

use Illuminate\Support\Facades\Route;
use Modules\Coupon\Http\Controllers\CouponController;
use Modules\Coupon\Http\Controllers\CouponReviewController;

Route::middleware('api')->group(function () {
    // Public coupon routes
    Route::get('coupons', [CouponController::class, 'index']);
    Route::get('coupons/{id}', [CouponController::class, 'show']);
    Route::get('coupons/{id}/details', [CouponController::class, 'details']);
    Route::post('coupons/{id}/calculate-discount', [CouponController::class, 'calculateDiscount']);
});

Route::middleware('auth:sanctum')->group(function () {
    // Public coupon review routes
    Route::get('coupons/{coupon}/reviews', [CouponReviewController::class, 'index']);
    Route::post('coupons/{coupon}/reviews', [CouponReviewController::class, 'store']);
    Route::put('coupons/{coupon}/reviews/{review}', [CouponReviewController::class, 'update']);
    Route::delete('coupons/{coupon}/reviews/{review}', [CouponReviewController::class, 'destroy']);
    
    // Top rated coupons
    Route::get('coupons/top-rated', [CouponReviewController::class, 'topRated']);
});

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('coupon-reviews', [CouponReviewController::class, 'adminIndex']);
    Route::patch('coupon-reviews/{review}/status', [CouponReviewController::class, 'updateStatus']);
});