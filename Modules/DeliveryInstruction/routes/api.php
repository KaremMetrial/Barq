<?php

use Illuminate\Support\Facades\Route;
use Modules\DeliveryInstruction\Http\Controllers\DeliveryInstructionController;

Route::prefix('v1')->group(function () {
    Route::apiResource('deliveryinstructions', DeliveryInstructionController::class)->names('deliveryinstruction');
});
