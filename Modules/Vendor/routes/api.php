<?php

use Illuminate\Support\Facades\Route;
use Modules\Vendor\Http\Controllers\VendorController;

Route::prefix('v1')->group(function () {
    Route::apiResource('vendors', VendorController::class)->names('vendor');
});
