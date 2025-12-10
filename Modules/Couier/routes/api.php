
<?php

use Illuminate\Support\Facades\Route;
use Modules\Couier\Http\Controllers\CourierController;
use Modules\Couier\Http\Controllers\CourierAuthController;
use Modules\Couier\Http\Controllers\Admin\ShiftTemplateController;
use Modules\Couier\Http\Controllers\Admin\CourierShiftController as AdminCourierShiftController;
use Modules\Couier\Http\Controllers\CourierShiftController;

Route::prefix('v1')->group(function () {
    // Public Courier Authentication Routes
    Route::prefix('courier')->group(function () {
        Route::post('register', [CourierAuthController::class, 'register'])->name('courier.register');
        Route::post('login', [CourierAuthController::class, 'login'])->name('courier.login');
        Route::post('logout', [CourierAuthController::class, 'logout'])->middleware('auth:sanctum')->name('courier.logout');
        Route::put('profile', [CourierAuthController::class, 'updateProfile'])->middleware('auth:sanctum')->name('courier.profile.update');
        Route::delete('delete-account', [CourierAuthController::class, 'deleteAccount'])->middleware('auth:sanctum')->name('courier.delete-account');
    });
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
        Route::prefix('couiers')->group(function () {
            Route::get('stats', [CouierController::class, 'stats']);
        });

        Route::apiResource('couiers', CouierController::class)->names('couier');

        // Shift Template Management
        Route::prefix('shift-templates')->group(function () {
            Route::get('/', [ShiftTemplateController::class, 'index']);
            Route::post('/', [ShiftTemplateController::class, 'store']);
            Route::get('/{id}', [ShiftTemplateController::class, 'show']);
            Route::put('/{id}', [ShiftTemplateController::class, 'update']);
            Route::delete('/{id}', [ShiftTemplateController::class, 'destroy']);
            Route::patch('/{id}/toggle', [ShiftTemplateController::class, 'toggle']);
        });

        // Courier Shift Management
        Route::prefix('courier-shifts')->group(function () {
            Route::get('/', [AdminCourierShiftController::class, 'index']);
            Route::post('/', [AdminCourierShiftController::class, 'store']);
            Route::post('/{id}/close', [AdminCourierShiftController::class, 'close']);
            Route::get('/stats', [AdminCourierShiftController::class, 'stats']);
        });

        // Get shifts for specific courier
        Route::get('couriers/{id}/shifts', [AdminCourierShiftController::class, 'courierShifts']);
    });

    // Courier Routes
    Route::prefix('courier')->middleware(['auth:sanctum', 'ability:courier'])->name('courier.')->group(function () {
        // Shift Templates (view available)
        Route::get('shift-templates', [CourierShiftController::class, 'templates']);

        // Shift Operations
        Route::prefix('shifts')->group(function () {
            Route::get('/', [CourierShiftController::class, 'index']);
            Route::post('/start', [CourierShiftController::class, 'start']);
            Route::get('/current', [CourierShiftController::class, 'current']);
            Route::post('/{id}/end', [CourierShiftController::class, 'end']);
            Route::post('/{id}/break/start', [CourierShiftController::class, 'startBreak']);
            Route::post('/{id}/break/end', [CourierShiftController::class, 'endBreak']);
            Route::get('/stats', [CourierShiftController::class, 'stats']);
        });
    });
});
