<?php

use Illuminate\Support\Facades\Route;
use Modules\Language\Http\Controllers\LanguageController;

Route::prefix('v1')->group(function () {
    Route::apiResource('languages', LanguageController::class)->names('language');
});
