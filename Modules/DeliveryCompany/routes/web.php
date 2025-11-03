<?php

use Illuminate\Support\Facades\Route;
use Modules\DeliveryCompany\Http\Controllers\DeliveryCompanyController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('deliverycompanies', DeliveryCompanyController::class)->names('deliverycompany');
});
