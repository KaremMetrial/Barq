<?php

use Illuminate\Support\Facades\Route;
use Modules\Conversation\Http\Controllers\ConversationController;
use Modules\Conversation\Http\Controllers\MessageController;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::middleware('auth:user')->group(function () {
        Route::prefix('conversations')
            ->controller(ConversationController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
            });

        Route::prefix('messages')
            ->controller(MessageController::class)
            ->group(function () {
                Route::get('/conversation/{conversationId}', 'index');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
                Route::post('/{messageId}/read', 'markAsRead');
                Route::post('/conversation/{conversationId}/typing', 'typingIndicator');
            });

        // Pusher authentication endpoint (for private channels)
        Route::post('/broadcasting/auth', [MessageController::class, 'pusherAuth']);
        // Optional alias if you’re already using this somewhere:
        Route::post('/pusher/auth', [MessageController::class, 'pusherAuth']);
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
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
        Route::prefix('conversations')
            ->controller(ConversationController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{id}', 'show');
                Route::put('/{id}', 'update');
                Route::put('/{id}/end', 'endConversation');
            });

        Route::prefix('messages')
            ->controller(MessageController::class)
            ->group(function () {
                Route::get('/conversation/{conversationId}', 'index');
                Route::post('/', 'store');
                Route::get('/{id}', 'show');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
            });
    });
});
