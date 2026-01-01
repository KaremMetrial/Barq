<?php

use Illuminate\Http\Request;
use App\Services\FirebaseService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\DeepLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TransactionController;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('v1/deeplink/resolve', [DeepLinkController::class,'resolve']);

Route::post('fcmTest', function (FirebaseService $firebaseService) {
   return $firebaseService->fcmTest();
});
Route::prefix('v1/admin')->middleware(['auth:sanctum','ability:admin,vendor'])->group(function () {
    Route::get('dashboard', DashboardController::class);
    // Route::get()

    // Transaction routes
    Route::apiResource('transactions', TransactionController::class)->names('admin.transactions');
    Route::get('transactions/stats', [TransactionController::class, 'stats'])->name('admin.transactions.stats');
    Route::post('transactions/pay', [TransactionController::class, 'pay'])->name('admin.transactions.pay');
});

Route::put('v1/update-token', [TokenController::class, 'updateToken'])->middleware('auth:sanctum')->name('update.token');
