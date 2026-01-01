<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\AdminOrderController;

// Admin routes - full access to all orders
Route::prefix('v1')->group(function () {
    // Admin Order Management Routes
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin,vendor'])->name('admin.')->group(function () {
        Route::get('orders/stats', [AdminOrderController::class, 'stats'])->name('order.stats');
        Route::put('orders/{id}/status', [OrderController::class, 'updateStatus'])->name('order.update-status');
        Route::apiResource('orders', AdminOrderController::class)->names('order');
    });

    // Courier routes - access to orders assigned to them
    Route::middleware(['auth:sanctum', 'ability:courier'])->prefix('v1/courier')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('courier.orders');
    });
});

// User routes - access to their orders only
Route::middleware('auth:user')->prefix('v1')->group(function () {
    Route::apiResource('orders', OrderController::class)->names('order');
});
