<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeepLinkController;
use App\Services\FirebaseService;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');
Route::get('v1/deeplink/resolve', [DeepLinkController::class,'resolve']);

Route::post('fcmTest', function (FirebaseService $firebaseService) {
   return $firebaseService->fcmTest();
});