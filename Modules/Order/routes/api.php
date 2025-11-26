<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\AdminOrderController;

// Admin routes - full access to all orders
Route::prefix('v1')->group(function () {
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin,vendor'])->name('admin.')->group(function () {
        Route::get('orders/stats', [AdminOrderController::class, 'stats'])->name('order.stats');
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('order.update-status');
        Route::apiResource('orders', AdminOrderController::class)->names('order');
    });
});

// User routes - access to their orders only
Route::middleware('auth:user')->prefix('v1')->group(function () {
    Route::apiResource('orders', OrderController::class)->names('order');
});
