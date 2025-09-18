<?php

use Illuminate\Support\Facades\Route;
use Modules\Compaign\Http\Controllers\CompaignController;

Route::prefix('v1')->group(function () {
    Route::apiResource('compaigns', CompaignController::class)->names('compaign');
});
