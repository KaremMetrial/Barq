<?php

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\AddressController;

Route::middleware(['auth:user'])->prefix('v1')->group(function () {
    Route::apiResource('addresses', AddressController::class)->names('address');
});
Route::prefix('v1')->group(function () {
    Route::get('address-by-lat-long', [AddressController::class, 'byLatLong']);
});

Route::middleware(['auth:vendor'])->prefix('vendor')->group(function () {
    Route::apiResource('addresses', AddressController::class)->names('address');
});
