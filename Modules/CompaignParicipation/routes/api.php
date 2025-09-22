<?php

use Illuminate\Support\Facades\Route;
use Modules\CompaignParicipation\Http\Controllers\CompaignParicipationController;

Route::prefix('v1')->group(function () {
    Route::apiResource('compaignparicipations', CompaignParicipationController::class)->names('compaignparicipation');
});
