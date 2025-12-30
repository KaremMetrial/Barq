<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

        return $this->errorResponse('Failed to update location', 500);
    }
}
