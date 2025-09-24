<?php

use Illuminate\Support\Facades\Route;
use Modules\Option\Http\Controllers\OptionController;

Route::prefix('v1')->group(function () {
    Route::apiResource('options', OptionController::class)->names('option');
});
