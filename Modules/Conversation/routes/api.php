<?php

use Illuminate\Support\Facades\Route;
use Modules\Conversation\Http\Controllers\MessageController;
use Modules\Conversation\Http\Controllers\ConversationController;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:user')
    ->prefix('v1/conversations')
    ->controller(ConversationController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

Route::middleware('auth:user')
    ->prefix('v1/messages')
    ->controller(MessageController::class)
    ->group(function () {
        Route::get('/conversation/{conversationId}', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

/*
|--------------------------------------------------------------------------
| Vendor Routes
|--------------------------------------------------------------------------
*/
// Route::middleware('auth:vendor')
//     ->prefix('vendor/conversations')
//     ->controller(ConversationController::class)
//     ->group(function () {
//         Route::get('/', 'index');
//         Route::post('/', 'store');
//         Route::get('/{id}', 'show');
//         Route::put('/{id}', 'update');
//         Route::delete('/{id}', 'destroy');
//     });

// Route::middleware('auth:vendor')
//     ->prefix('vendor/messages')
//     ->controller(MessageController::class)
//     ->group(function () {
//         Route::get('/conversation/{conversationId}', 'index');
//         Route::post('/', 'store');
//         Route::get('/{id}', 'show');
//         Route::put('/{id}', 'update');
//         Route::delete('/{id}', 'destroy');
//     });

/*
|--------------------------------------------------------------------------
| Admin/Support Routes
|--------------------------------------------------------------------------
*/
// Route::middleware('auth:sanctum')
//     ->prefix('admin/conversations')
//     ->controller(ConversationController::class)
//     ->group(function () {
//         Route::get('/', 'index');
//         Route::get('/{id}', 'show');
//         Route::put('/{id}', 'update');
//         Route::put('/{id}/end', 'endConversation');
//     });

// Route::middleware('auth:sanctum')
//     ->prefix('admin/messages')
//     ->controller(MessageController::class)
//     ->group(function () {
//         Route::get('/conversation/{conversationId}', 'index');
//         Route::post('/', 'store');
//         Route::get('/{id}', 'show');
//         Route::put('/{id}', 'update');
//         Route::delete('/{id}', 'destroy');
//     });
