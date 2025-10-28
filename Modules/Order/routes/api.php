<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;
use Modules\Order\Http\Controllers\AdminOrderController;
use Modules\Order\Http\Controllers\VendorOrderController;

// Admin routes - full access to all orders
Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
    Route::apiResource('orders', AdminOrderController::class)->only(['index', 'show', 'update'])->names('order');
});

// Vendor routes - access to their store orders only
Route::prefix('vendor')->middleware('auth:vendor')->name('vendor.')->group(function () {
    Route::apiResource('orders', VendorOrderController::class)->only(['index', 'show', 'update'])->names('order');
});

// User routes - access to their orders only
Route::middleware('auth:user')->prefix('v1')->group(function () {
    Route::apiResource('orders', OrderController::class)->names('order');
});
