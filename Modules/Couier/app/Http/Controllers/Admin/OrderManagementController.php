<?php

namespace Modules\Couier\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
// use Modules\Couier\Services\SmartOrderAssignmentService;
use Modules\Couier\Services\GeographicCourierService;
use Modules\Couier\Models\CourierOrderAssignment;

class OrderManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        // protected SmartOrderAssignmentService $assignmentService,
        protected GeographicCourierService $geographicService
    ) {}

    /**
     * Manually assign order to nearest courier (admin override)
     */
    public function assignToNearestCourier(Request $request): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'pickup_lat' => 'required|numeric|between:-90,90',
            'pickup_lng' => 'required|numeric|between:-180,180',
            'delivery_lat' => 'numeric|between:-90,90',
            'delivery_lng' => 'numeric|between:-180,180',
            'priority_level' => 'in:low,normal,high,urgent',
            'timeout_seconds' => 'integer|min:30|max:600'
        ]);

        try {
            // Check if order is already assigned
            $existingAssignment = CourierOrderAssignment::where('order_id', $request->order_id)
                ->whereIn('status', ['assigned', 'accepted', 'in_transit'])
                ->first();

            if ($existingAssignment) {
                return $this->errorResponse('Order is already assigned to courier', 422);
            }

            $orderData = [
                'order_id' => $request->order_id,
                'pickup_lat' => $request->pickup_lat,
                'pickup_lng' => $request->pickup_lng,
                'delivery_lat' => $request->delivery_lat,
                'delivery_lng' => $request->delivery_lng,
                'priority_level' => $request->priority_level ?? 'normal',
            ];

            // $assignment = $this->assignmentService->autoAssignOrder(
            //     $orderData,
            //     $request->timeout_seconds ?? 120
            // );

            // if (!$assignment) {
            //     return $this->errorResponse('No suitable couriers found nearby', 404);
            // }

            return $this->successResponse([
                // 'assignment_id' => $assignment->id,
                // 'courier_id' => $assignment->courier_id,
                // 'estimated_distance_km' => $assignment->estimated_distance_km,
                // 'expires_at' => $assignment->expires_at->toISOString(),
            ], 'Order assigned to nearest courier');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Monitoring dashboard for admin
     */
    public function monitoringDashboard(Request $request): JsonResponse
    {
        $request->validate([
            'lat' => 'numeric|between:-90,90',
            'lng' => 'numeric|between:-180,180',
            'radius_km' => 'numeric|min:1|max:50'
        ]);

        try {
            $centerLat = $request->lat ?? 30.0444; // Cairo default
            $centerLng = $request->lng ?? 31.2357;
            $radius = $request->radius_km ?? 10;

            // Active couriers in area
            $activeCouriers = $this->geographicService->findNearestActiveCouriers($centerLat, $centerLng, $radius);

            // Active assignments
            $activeAssignments = CourierOrderAssignment::whereIn('status', ['assigned', 'accepted', 'in_transit'])
                ->with(['courier', 'order'])
                ->get();

            // System statistics
            $stats = [
                'active_couriers' => $activeCouriers->count(),
                'active_orders' => $activeAssignments->count(),
                'orders_waiting' => CourierOrderAssignment::where('status', 'assigned')->count(),
                'orders_in_transit' => CourierOrderAssignment::where('status', 'in_transit')->count(),
                'orders_expired_today' => CourierOrderAssignment::where('status', 'timed_out')
                    ->whereDate('updated_at', today())->count(),
            ];

            // Recent activity
            $recentActivity = CourierOrderAssignment::orderBy('updated_at', 'desc')
                ->limit(20)
                ->with(['courier:id,first_name,last_name'])
                ->get(['id', 'courier_id', 'order_id', 'status', 'updated_at']);

            return $this->successResponse([
                'stats' => $stats,
                'active_couriers' => $activeCouriers->map(function ($courier) {
                    return [
                        'id' => $courier->id,
                        'name' => $courier->first_name . ' ' . $courier->last_name,
                        'location' => $courier->distance_km ? round($courier->distance_km, 2) . 'km away' : null,
                        'status' => $courier->avaliable_status?->value ?? 'unknown',
                    ];
                }),
                'active_orders' => $activeAssignments->map(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'order_id' => $assignment->order_id,
                        'courier' => $assignment->courier ? $assignment->courier->first_name . ' ' . $assignment->courier->last_name : 'Unassigned',
                        'status' => $assignment->status,
                        'expires_in' => $assignment->time_remaining,
                        'pickup_location' => $assignment->pickup_coordinates,
                        'delivery_location' => $assignment->delivery_coordinates,
                    ];
                }),
                'recent_activity' => $recentActivity->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'courier' => $activity->courier?->first_name . ' ' . $activity->courier?->last_name,
                        'order_id' => $activity->order_id,
                        'status' => $activity->status,
                        'timestamp' => $activity->updated_at->toISOString(),
                    ];
                }),
                'map_center' => [
                    'lat' => $centerLat,
                    'lng' => $centerLng,
                    'zoom' => 12,
                ],
            ], 'Monitoring dashboard data retrieved');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Manually reassign an order to a different courier
     */
    public function reassignOrder(Request $request, $assignmentId): JsonResponse
    {
        $request->validate([
            'courier_id' => 'required|integer|exists:couiers,id',
            'reason' => 'nullable|string|max:255'
        ]);

        try {
            $assignment = CourierOrderAssignment::findOrFail($assignmentId);
            $oldCourierId = $assignment->courier_id;
            $newCourierId = $request->courier_id;

            if ($oldCourierId === $newCourierId) {
                return $this->errorResponse('Order is already assigned to this courier', 422);
            }

            // Check if new courier is available
            $newCourier = \Modules\Couier\Models\Couier::find($newCourierId);
            if (!$newCourier || $newCourier->status->value !== 'active' || $newCourier->avaliable_status->value !== 'available') {
                return $this->errorResponse('Selected courier is not available', 422);
            }

            // Expire old assignment
            $assignment->update([
                'status' => 'timed_out',
                'notes' => ($assignment->notes ? $assignment->notes . ' | ' : '') . 'Reassigned by admin: ' . ($request->reason ?? 'Manual reassignment'),
            ]);

            // Create new assignment
            $newAssignmentData = [
                'order_id' => $assignment->order_id,
                'pickup_lat' => $assignment->pickup_lat,
                'pickup_lng' => $assignment->pickup_lng,
                'delivery_lat' => $assignment->delivery_lat,
                'delivery_lng' => $assignment->delivery_lng,
                'priority_level' => $assignment->priority_level,
                'courier_shift_id' => $assignment->courier_shift_id,
            ];

            // $newAssignment = $this->assignmentService->assignToCourier(
            //     $newCourierId,
            //     $newAssignmentData,
            //     120 // 2 minutes timeout
            // );

            // if (!$newAssignment) {
            //     // If reassignment failed, reactivate old assignment
            //     $assignment->update(['status' => 'assigned']);
            //     return $this->errorResponse('Reassignment failed - courier unavailable', 500);
            // }

            // Log reassignment in notes
            // $newAssignment->update([
            //     'notes' => 'Reassigned from courier #' . $oldCourierId . ' - ' . ($request->reason ?? 'Admin reassignment')
            // ]);

            // return $this->successResponse([
            //     // 'old_assignment_id' => $assignmentId,
            //     // 'new_assignment_id' => $newAssignment->id,
            //     'new_courier_id' => $newCourierId,
            //     'reassignment_reason' => $request->reason ?? 'Manual reassignment',
            // ], 'Order reassigned successfully');

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}
