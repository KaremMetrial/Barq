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
     * Assign a shift template to a courier
     */
    public function assignTemplate(Request $request, int $courierId): JsonResponse
    {
        $request->validate([
            'shift_template_id' => 'required|exists:shift_templates,id',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Check if courier exists
            $courier = \Modules\Couier\Models\Couier::findOrFail($courierId);

            // Verify permissions if vendor
            if (auth('vendor')->check()) {
                $vendorStoreId = auth('vendor')->user()->store_id;

                if ($courier->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only assign shifts to couriers in your store'), 403);
                }

                // Check if template belongs to vendor's store
                $template = \Modules\Couier\Models\ShiftTemplate::findOrFail($request->shift_template_id);
                if ($template->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only assign templates from your store'), 403);
                }
            }

            // Check for existing assignment
            $existing = \Modules\Couier\Models\CourierShiftTemplate::where('courier_id', $courierId)
                ->where('shift_template_id', $request->shift_template_id)
                ->first();

            if ($existing) {
                return $this->errorResponse(__('This template is already assigned to this courier'), 400);
            }

            // Create assignment
            $assignment = \Modules\Couier\Models\CourierShiftTemplate::create([
                'courier_id' => $courierId,
                'shift_template_id' => $request->shift_template_id,
                'assigned_by' => auth('sanctum')->id(),
                'notes' => $request->notes,
            ]);

            return $this->successResponse([
                'assignment' => $assignment->load('shiftTemplate.days'),
                'courier' => $courier->only(['id', 'first_name', 'last_name'])
            ], __('Shift template assigned successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get assigned templates for a courier
     */
    public function getCourierTemplates(int $courierId): JsonResponse
    {
        try {
            // Verify permissions if vendor
            if (auth('vendor')->check()) {
                $courier = \Modules\Couier\Models\Couier::findOrFail($courierId);
                $vendorStoreId = auth('vendor')->user()->store_id;

                if ($courier->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only view templates for couriers in your store'), 403);
                }
            }

            $assignments = \Modules\Couier\Models\CourierShiftTemplate::where('courier_id', $courierId)
                ->with('shiftTemplate.days')
                ->get();

            return $this->successResponse([
                'assignments' => $assignments
            ], __('Courier templates retrieved successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove shift template assignment from courier
     */
    public function removeTemplate(Request $request, int $courierId, int $templateId): JsonResponse
    {
        try {
            // Verify permissions if vendor
            if (auth('vendor')->check()) {
                $courier = \Modules\Couier\Models\Couier::findOrFail($courierId);
                $vendorStoreId = auth('vendor')->user()->store_id;

                if ($courier->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only manage templates for couriers in your store'), 403);
                }

                // Check if template belongs to vendor's store
                $template = \Modules\Couier\Models\ShiftTemplate::findOrFail($templateId);
                if ($template->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('This template does not belong to your store'), 403);
                }
            }

            $assignment = \Modules\Couier\Models\CourierShiftTemplate::where('courier_id', $courierId)
                ->where('shift_template_id', $templateId)
                ->firstOrFail();

            $assignment->delete();

            return $this->successResponse([], __('Shift template assignment removed successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

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
     * Start a shift for a courier manually
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'couier_id' => 'required|exists:couiers,id',
            'shift_template_id' => 'required|exists:shift_templates,id'
        ]);

        try {
            // Verify if the courier belongs to the vendor's store (if vendor)
            if (auth('vendor')->check()) {
                $vendorStoreId = auth('vendor')->user()->store_id;
                $courier = \Modules\Couier\Models\Couier::find($request->couier_id);

                if ($courier->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only assign shifts to your own couriers'), 403);
                }

                // Verify if template belongs to vendor's store
                $template = \Modules\Couier\Models\ShiftTemplate::find($request->shift_template_id);
                if ($template->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only use your own shift templates'), 403);
                }
            }

            $shift = $this->courierShiftService->startShift($request->couier_id, $request->shift_template_id);

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift started successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
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
     * Schedule a shift for a courier (assign shift without starting immediately)
     */
    public function schedule(Request $request): JsonResponse
    {
        $request->validate([
            'couier_id' => 'required|exists:couiers,id',
            'shift_template_id' => 'required|exists:shift_templates,id',
            'scheduled_date' => 'required|date|after:today',
            'scheduled_start_time' => 'nullable|date_format:H:i:s',
            'scheduled_end_time' => 'nullable|date_format:H:i:s',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            // Verify permissions
            if (auth('vendor')->check()) {
                $vendorStoreId = auth('vendor')->user()->store_id;
                $courier = \Modules\Couier\Models\Couier::find($request->couier_id);

                if ($courier->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only assign shifts to your own couriers'), 403);
                }

                $template = \Modules\Couier\Models\ShiftTemplate::find($request->shift_template_id);
                if ($template->store_id !== $vendorStoreId) {
                    return $this->errorResponse(__('You can only use your own shift templates'), 403);
                }
            }

            $shift = $this->courierShiftService->scheduleShift(
                $request->couier_id,
                $request->shift_template_id,
                $request->scheduled_date,
                $request->scheduled_start_time,
                $request->scheduled_end_time,
                $request->notes
            );

            return $this->successResponse([
                'shift' => new CourierShiftResource($shift)
            ], __('Shift scheduled successfully'));
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
