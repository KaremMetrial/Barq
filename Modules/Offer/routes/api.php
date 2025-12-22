<?php

use Illuminate\Support\Facades\Route;
use Modules\Offer\Http\Controllers\OfferController;

Route::prefix('v1/admin')->middleware(['auth:sanctum', 'ability:admin,vendor'])->group(function () {
    Route::post('offers/{id}/toggle-status', [OfferController::class, 'toggleStatus'])->name('offer.toggle-status');
    Route::apiResource('offers', OfferController::class)->names('offer');
});
