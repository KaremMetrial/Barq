<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Http\Controllers\PromotionController;

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('promotions', PromotionController::class)->names('promotion');
    Route::get('promotions/{id}/validate', [PromotionController::class, 'validate'])->name('promotion.validate');
    Route::post('promotions/{id}/apply', [PromotionController::class, 'apply'])->name('promotion.apply');

});
