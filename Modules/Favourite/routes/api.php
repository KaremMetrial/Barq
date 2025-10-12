<?php

use Illuminate\Support\Facades\Route;
use Modules\Favourite\Http\Controllers\FavouriteController;

Route::middleware('auth:user')->prefix('v1')->group(function () {
    Route::apiResource('favourites', FavouriteController::class)->names('favourite');
});
