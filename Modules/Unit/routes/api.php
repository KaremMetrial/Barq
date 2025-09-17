<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Http\Controllers\UnitController;

Route::prefix('v1')->group(function () {
    Route::apiResource('units', UnitController::class)->names('unit');
});
