<?php

namespace Modules\Couier\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Couier\Services\CourierShiftService;
use App\Http\Resources\PaginationResource;
use Modules\Couier\Http\Resources\CourierShiftResource;

class CourierShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CourierShiftService $courierShiftService
    ) {}

    /**
     * Get all courier shifts
     */
    public function index(Request $request): JsonResponse
    {
        $shifts = $this->courierShiftService->getAllShifts($request->all());

        return $this->successResponse([
            'shifts' => CourierShiftResource::collection($shifts),
            'pagination' => new PaginationResource($shifts)
        ], __('message.success'));
    }

    /**
     * Get shifts for specific courier
     */
    public function courierShifts(Request $request, int $courierId): JsonResponse
    {
        $filters = array_merge($request->all(), ['couier_id' => $courierId]);
        $shifts = $this->courierShiftService->getAllShifts($filters);

        return $this->successResponse([
            'shifts' => CourierShiftResource::collection($shifts),
            'pagination' => new PaginationResource($shifts)
        ], __('message.success'));
    }

    /**
     * Close a shift
     */
    public function close(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->closeShift($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('message.updated'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get overall statistics
     */
    public function stats(Request $request): JsonResponse
    {
        // This would aggregate stats across all couriers
        // For now, returning a simple implementation
        return $this->successResponse([
            'message' => 'Overall statistics endpoint - to be implemented'
        ], __('message.success'));
    }
}
