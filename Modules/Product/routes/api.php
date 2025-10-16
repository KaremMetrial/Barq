<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\Admin\ProductController as AdminProductController;
use Modules\Product\Http\Controllers\Vendor\ProductController as VendorProductController;


Route::prefix('v1')->group(function () {
    Route::prefix('products')->controller(ProductController::class)->group(function () {
        Route::get('/offers-ending-soon',  'getOffersEndingSoon');
        Route::get('/home', 'home')->name('product.home');
        Route::get('stores/{store}/grouped-products', 'groupedProductsByStore')->name('product.groupedProductsByStore');
        Route::get('/', 'index')->name('product.index');
        Route::get('/{id}', 'show')->name('product.show');
    });
    Route::prefix('admin')->middleware('auth:admin')->name('admin.')->group(function () {
        Route::apiResource('products', AdminProductController::class)->names('product');
    });
    Route::prefix('vendor')->middleware('auth:vendor')->name('vendor.')->group(function () {
        Route::apiResource('products', VendorProductController::class)->names('product');
    });
});
