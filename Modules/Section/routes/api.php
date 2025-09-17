<?php

use Illuminate\Support\Facades\Route;
use Modules\Section\Http\Controllers\SectionController;

Route::prefix('v1')->group(function () {
    Route::apiResource('sections', SectionController::class)->names('section');
});
