<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;

Route::prefix('v1')->group(function () {
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/home', 'home')->name('product.home');
        Route::get('stores/{store}/grouped-products', 'groupedProductsByStore')->name('product.groupedProductsByStore');
    });
    Route::apiResource('products', ProductController::class)->names('product');
});
