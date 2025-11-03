<?php

use Illuminate\Support\Facades\Route;
use Modules\DeliveryCompany\Http\Controllers\DeliveryCompanyController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('deliverycompanies', DeliveryCompanyController::class)->names('deliverycompany');
});
