
<?php

use Illuminate\Support\Facades\Route;
use Modules\Couier\Http\Controllers\CouierController;
use Modules\Couier\Http\Controllers\CourierMapController;
use Modules\Couier\Http\Controllers\CourierAuthController;
use Modules\Couier\Http\Controllers\CourierShiftController;
use Modules\Couier\Http\Controllers\CourierLocationController;
use Modules\Couier\Http\Controllers\CourierDashboardController;
use Modules\Couier\Http\Controllers\Admin\ShiftTemplateController;
use Modules\Couier\Http\Controllers\Admin\OrderManagementController;
use Modules\Couier\Http\Controllers\Admin\CourierShiftController as AdminCourierShiftController;

Route::prefix('v1')->group(function () {
    // Public Courier Authentication Routes
    Route::prefix('courier')->group(function () {
        Route::post('register', [CourierAuthController::class, 'register'])->name('courier.register');
        Route::post('login', [CourierAuthController::class, 'login'])->name('courier.login');
        Route::post('logout', [CourierAuthController::class, 'logout'])->middleware('auth:sanctum')->name('courier.logout');
        Route::get('profile', [CourierAuthController::class, 'profile'])->middleware('auth:sanctum')->name('courier.profile');
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

        // Courier Shift Template Assignments
        Route::prefix('couriers/{courierId}/templates')->group(function () {
            Route::post('/', [AdminCourierShiftController::class, 'assignTemplate']);
            Route::get('/', [AdminCourierShiftController::class, 'getCourierTemplates']);
            Route::delete('/{templateId}', [AdminCourierShiftController::class, 'removeTemplate']);
        });

        // Courier Shift Management
        Route::prefix('courier-shifts')->group(function () {
            Route::get('/', [AdminCourierShiftController::class, 'index']);
            Route::post('/', [AdminCourierShiftController::class, 'store']);
            Route::post('/schedule', [AdminCourierShiftController::class, 'schedule']);
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
            Route::get('/schedule', [CourierShiftController::class, 'schedule']);
            Route::get('/schedule-shifts', [CourierShiftController::class, 'scheduleShifts']);
            Route::get('/calendar', [CourierShiftController::class, 'calendarSchedule']);
            Route::post('/start', [CourierShiftController::class, 'start']);
            Route::get('/current', [CourierShiftController::class, 'current']);
            Route::get('/next', [CourierShiftController::class, 'next']);
            Route::post('/{id}/end', [CourierShiftController::class, 'end']);
            Route::post('/{id}/break/start', [CourierShiftController::class, 'startBreak']);
            Route::post('/{id}/break/end', [CourierShiftController::class, 'endBreak']);
            Route::get('/stats', [CourierShiftController::class, 'stats']);
        });

        // Dashboard
        Route::get('/dashboard', [CourierDashboardController::class, 'index']);
        Route::get('/earnings-details', [CourierDashboardController::class, 'earningsDetails']);

        // Order Management Routes
        Route::prefix('orders')->group(function () {
            // Map View & Location Updates
            Route::get('/map/active', [CourierMapController::class, 'activeOrdersMap']);

            // Assignment Response
            Route::post('/assignments/{assignmentId}/respond', [CourierMapController::class, 'respondToAssignment']);

            // Status Updates
            Route::put('/assignments/{assignmentId}/status', [CourierMapController::class, 'updateOrderStatus']);

            // Order Details
            Route::get('/assignments/{assignmentId}/details', [CourierMapController::class, 'orderDetails']);
            Route::get('/assignments/{assignmentId}/comprehensive-details', [CourierMapController::class, 'comprehensiveOrderDetails']);
            Route::get('/assignments/{assignmentId}/full-details', [CourierMapController::class, 'fullOrderDetails'])->name('courier.full-order-details');

            // Receipt Upload & Management
            Route::post('/assignments/{assignmentId}/upload-receipt', [CourierMapController::class, 'uploadPickupReceipt'])->name('courier.upload-pickup-receipt');
            Route::post('/assignments/{assignmentId}/upload-delivery-proof', [CourierMapController::class, 'uploadDeliveryProof'])->name('courier.upload-delivery-proof');
            Route::delete('/assignments/{assignmentId}/receipts/{receiptId}', [CourierMapController::class, 'deleteReceipt'])->name('courier.delete-receipt');

            // Earnings Summary
            Route::get('/earnings/summary', [CourierMapController::class, 'earningsSummary']);
        });

        Route::post('/location/update', [CourierLocationController::class, 'updateLocation']);
    });

    // Admin Order Management
    Route::prefix('admin')->middleware(['auth:sanctum', 'ability:admin'])->group(function () {
        Route::prefix('orders')->group(function () {
            // Manual assignment and monitoring (to be implemented)
            Route::post('/assign/nearest', [OrderManagementController::class, 'assignToNearestCourier']);
            Route::get('/monitoring/dashboard', [OrderManagementController::class, 'monitoringDashboard']);
            Route::put('/assignments/{id}/reassign', [OrderManagementController::class, 'reassignOrder']);
        });
    });
});
