<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeepLinkController;
use Modules\Store\Http\Controllers\User\StoreController;
use Modules\Store\Http\Controllers\Admin\StoreController as AdminStoreController;
use Modules\Store\Http\Controllers\Vendor\StoreController as VendorStoreController;

Route::prefix('v1')->group(function () {
    Route::prefix('stores')->controller(StoreController::class)->group(function () {
        Route::get('/', 'index')->name('store.index');
        Route::get('/home', 'home')->name('store.home');
        Route::get('/{id}', 'show')->name('store.show');
    });
    Route::post('/generate/store/{id}', [DeepLinkController::class, 'generateStore']);


    // Admin
    Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
        Route::prefix('stores')->name('stores.')->group(function () {
            Route::get('/stats', [AdminStoreController::class, 'stats'])->name('status');
            Route::get('delivery', [AdminStoreController::class, 'deliveryStore']);
            Route::get('delivery/stats', [AdminStoreController::class, 'deliveryStoreStats']);
            Route::get('/vendor/stats', [VendorStoreController::class, 'vendorStats'])->name('vendor.store.stats');
            Route::get('/commission-settings', [AdminStoreController::class, 'commissionSettings'])->name('commission.settings');

            Route::get('/{id}/branches', [AdminStoreController::class, 'branches'])->name('branches');
            Route::get('/{id}/delivery', [AdminStoreController::class, 'deliveryStoreInfo'])->name('deliveryStoreInfo');
            Route::get('/{id}/delivery/zone-to-cover', [AdminStoreController::class, 'deliveryStoreZoneToCover'])->name('deliveryStoreZoneToCover');
        });


        Route::apiResource('stores', AdminStoreController::class)->names('store');
    });
});
