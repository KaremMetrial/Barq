<?php

use Illuminate\Support\Facades\Route;
use Modules\ShippingPrice\Http\Controllers\ShippingPriceController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::prefix('admin')->middleware('ability:admin')->group(function () {
        Route::apiResource('shippingprices', ShippingPriceController::class)->names('shippingprice');
    });
});
