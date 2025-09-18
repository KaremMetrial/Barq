<?php

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\AddressController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('addresses', AddressController::class)->names('address');
});
