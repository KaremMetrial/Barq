<?php

use Illuminate\Support\Facades\Route;
use Modules\Search\Http\Controllers\SearchController;

Route::prefix('v1')->group(function () {
    Route::controller(SearchController::class)->prefix('search')->group(function () {
        Route::get('/autocomplete', 'autocomplete');
        Route::get('/',  'search');
        Route::get('top-logs', 'getTopSearchLogs');
    });
});
