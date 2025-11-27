<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DeepLinkController;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Store\Http\Controllers\User\StoreController;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/r/{token}', [DeepLinkController::class, 'redirect']);

Route::post('/generate/product/{id}', [DeepLinkController::class, 'generateProduct']);
Route::post('/generate/store/{id}', [DeepLinkController::class, 'generateStore']);

Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/stores/{id}', [StoreController::class, 'show']);
