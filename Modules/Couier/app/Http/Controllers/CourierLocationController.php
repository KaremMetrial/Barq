<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Modules\Couier\Services\CourierLocationCacheService;

class CourierLocationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CourierLocationCacheService $locationCache
    ) {}

    /**
     * Update courier location in cache
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
        ]);

        $courierId = auth('sanctum')->id();

        if (!$courierId) {
            Log::error('Courier location update failed: Not authenticated');
            return $this->errorResponse('Authentication required', 401);
        }

        try {
            $success = $this->locationCache->updateCourierLocation(
                $courierId,
                $request->latitude,
                $request->longitude,
                [
                    'accuracy' => $request->accuracy,
                    'speed' => $request->speed,
                    'heading' => $request->heading,
                ]
            );
            if ($success) {
                return $this->successResponse([
                    'updated_at' => now()->toISOString(),
                    'courier_id' => $courierId
                ], 'Location updated successfully');
            }

            Log::error('Courier location update failed: Cache service returned false', [
                'courier_id' => $courierId,
                'lat' => $request->latitude,
                'lng' => $request->longitude
            ]);
            return $this->errorResponse('Failed to update location', 500);
        } catch (\Exception $e) {
            Log::error('Courier location update exception', [
                'courier_id' => $courierId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->errorResponse('Internal server error', 500);
        }
    }
}
