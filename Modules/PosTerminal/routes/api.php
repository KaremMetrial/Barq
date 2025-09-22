<?php

use Illuminate\Support\Facades\Route;
use Modules\PosTerminal\Http\Controllers\PosTerminalController;

Route::prefix('v1')->group(function () {
    Route::apiResource('posterminals', PosTerminalController::class)->names('posterminal');
});
