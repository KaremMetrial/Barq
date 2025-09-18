<?php

use Illuminate\Support\Facades\Route;
use Modules\Tag\Http\Controllers\TagController;

Route::prefix('v1')->group(function () {
    Route::apiResource('tags', TagController::class)->names('tag');
});
