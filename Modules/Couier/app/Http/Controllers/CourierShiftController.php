<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\Couier\Services\ShiftTemplateService;
use Modules\Couier\Services\CourierShiftService;
use Modules\Couier\Http\Resources\ShiftTemplateResource;
use Modules\Couier\Http\Resources\CourierShiftResource;

class CourierShiftController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ShiftTemplateService $shiftTemplateService,
        protected CourierShiftService $courierShiftService
    ) {}

    /**
     * Get available shift templates
     */
    public function templates(): JsonResponse
    {
        $templates = $this->shiftTemplateService->getActiveTemplates();

        return $this->successResponse([
            'templates' => ShiftTemplateResource::collection($templates)
        ], __('message.success'));
    }

    /**
     * Start a new shift
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'shift_template_id' => 'required|exists:shift_templates,id'
        ]);

        try {
            $courierId = auth('sanctum')->id();
            $shift = $this->courierShiftService->startShift($courierId, $request->shift_template_id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift started successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * End current shift
     */
    public function end(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->endShift($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift ended successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Start break
     */
    public function startBreak(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->startBreak($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Break started'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * End break
     */
    public function endBreak(int $id): JsonResponse
    {
        try {
            $shift = $this->courierShiftService->endBreak($id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Break ended'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get my shifts
     */
    public function index(Request $request): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $shifts = $this->courierShiftService->getShiftHistory($courierId, $request->all());

        return $this->successResponse([
            'shifts' => CourierShiftResource::collection($shifts),
            'pagination' => new PaginationResource($shifts)
        ], __('message.success'));
    }

    /**
     * Get current active shift
     */
    public function current(): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $shift = $this->courierShiftService->getCurrentShift($courierId);

        return $this->successResponse([
            'shift' => $shift ? new CourierShiftResource($shift) : null
        ], __('message.success'));
    }

    /**
     * Get my statistics
     */
    public function stats(Request $request): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $stats = $this->courierShiftService->getStats($courierId, $request->all());

        return $this->successResponse([
            'stats' => $stats
        ], __('message.success'));
    }
}
