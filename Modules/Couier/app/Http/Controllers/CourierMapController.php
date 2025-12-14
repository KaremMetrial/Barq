<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Couier\Services\SmartOrderAssignmentService;
use Modules\Couier\Services\GeographicCourierService;
use Modules\Couier\Services\OrderReceiptService;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Couier\Http\Resources\FullOrderResource;
use Modules\Couier\Http\Resources\OrderReceiptResource;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CourierMapController extends Controller
{
    use ApiResponse;

    public function __construct(
        // protected SmartOrderAssignmentService $assignmentService,
        protected GeographicCourierService $geographicService,
        protected OrderReceiptService $receiptService
    ) {}

    /**
     * Get active orders map view for courier
     */
    public function activeOrdersMap(Request $request): JsonResponse
    {
        $request->validate([
            'current_lat' => 'required|numeric|between:-90,90',
            'current_lng' => 'required|numeric|between:-180,180',
            'radius_km' => 'numeric|min:0.5|max:20'
        ]);

        $courierId = auth('sanctum')->id();
        $radius = $request->radius_km ?? 5.0;

        try {
            // Update courier location
            // $this->assignmentService->updateCourierLocation(
            //     $courierId,
            //     $request->current_lat,
            //     $request->current_lng
            // );

            // Get courier's active assignments
            // $activeAssignments = $this->assignmentService
            //     ->getCourierActiveAssignments($courierId);

            // Find nearby pending assignments (that could be assigned to this courier)
            $nearbyPendingOrders = $this->findNearbyPendingOrders(
                $request->current_lat,
                $request->current_lng,
                $radius,
                $courierId
            );

            // Prepare map markers
            // $markers = $this->prepareMapMarkers($activeAssignments, $nearbyPendingOrders);

            // Get status summary
            // $statusSummary = $this->getCourierStatusSummary($courierId, $activeAssignments);

            return $this->successResponse([
                'map_view' => [
                    'center' => [
                        'lat' => $request->current_lat,
                        'lng' => $request->current_lng,
                    ],
                    'zoom' => $this->calculateOptimalZoom($radius),
                    // 'markers' => $markers,
                ],
                // 'status_summary' => $statusSummary,
                'last_location_update' => now()->toISOString(),
            ], __('Map data retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Handle order assignment response (accept/reject)
     */
    public function respondToAssignment(Request $request, $assignmentId): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'reason' => 'nullable|string|max:255' // Required for rejection
        ]);

        $courierId = auth('sanctum')->id();

        try {
            if ($request->action === 'accept') {
                // $success = $this->assignmentService->acceptAssignment($assignmentId, $courierId);
                // $message = $success ? __('Order accepted successfully') : __('Failed to accept order');
            } else {
                if (!$request->filled('reason')) {
                    return $this->errorResponse(__('Reason is required for rejection'), 422);
                }

                // $success = $this->assignmentService->rejectAssignment($assignmentId, $courierId, $request->reason);
                // $message = $success ? __('Order rejected') : __('Failed to reject order');
            }

            // $statusCode = $success ? 200 : 400;

            // return $this->successResponse([
            //     'assignment_id' => $assignmentId,
            //     'action' => $request->action,
            //     // 'success' => $success,
            // ], $message, $statusCode);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Update order delivery status
     */
    public function updateOrderStatus(Request $request, $assignmentId): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:accepted,in_transit,delivered,failed',
            'location_lat' => 'numeric|between:-90,90',
            'location_lng' => 'numeric|between:-180,180',
            'failure_reason' => 'required_if:status,failed|string|max:500'
        ]);

        $courierId = auth('sanctum')->id();

        try {
            // Verify the assignment belongs to this courier
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('Assignment not found or access denied'), 404);
            }

            // Validate status transition
            if (!$this->isValidStatusTransition($assignment->status, $request->status)) {
                return $this->errorResponse(__('Invalid status transition'), 422);
            }

            $updateData = [];
            if ($request->filled('failure_reason')) {
                $updateData = ['reason' => $request->failure_reason];
            }

            if ($request->filled(['location_lat', 'location_lng'])) {
                $updateData['current_courier_lat'] = $request->location_lat;
                $updateData['current_courier_lng'] = $request->location_lng;
            }

            $success = $this->assignmentService->updateAssignmentStatus(
                $assignmentId,
                $request->status,
                $updateData
            );

            if (!$success) {
                return $this->errorResponse(__('Failed to update order status'), 400);
            }

            return $this->successResponse([
                'assignment_id' => $assignmentId,
                'new_status' => $request->status,
                'updated_at' => now()->toISOString(),
            ], __('Order status updated successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get detailed order information
     */
    public function orderDetails($assignmentId): JsonResponse
    {
        $courierId = auth('sanctum')->id();

        try {
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->with(['order', 'courierShift'])
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('Assignment not found or access denied'), 404);
            }

            $details = [
                'assignment_id' => $assignment->id,
                'order_id' => $assignment->order_id,
                'status' => $assignment->status,
                'priority' => $assignment->priority_level,
                'expires_in' => $assignment->time_remaining,
                'assigned_at' => $assignment->assigned_at->toISOString(),

                'locations' => [
                    'pickup' => $assignment->pickup_coordinates,
                    'delivery' => $assignment->delivery_coordinates,
                    'current' => $assignment->current_coordinates,
                ],

                'estimates' => [
                    'distance_km' => $assignment->estimated_distance_km,
                    'duration_minutes' => $assignment->estimated_duration_minutes,
                    'earning' => $assignment->estimated_earning,
                ],

                'actuals' => [
                    'distance_km' => $assignment->actual_distance_km,
                    'duration_minutes' => $assignment->actual_duration_minutes,
                    'earning' => $assignment->actual_earning,
                ],

                'ratings' => [
                    'customer_rating' => $assignment->customer_rating,
                    'courier_rating' => $assignment->courier_rating,
                    'customer_feedback' => $assignment->customer_feedback,
                    'courier_feedback' => $assignment->courier_feedback,
                ],

                'timings' => [
                    'accepted_at' => $assignment->accepted_at?->toISOString(),
                    'started_at' => $assignment->started_at?->toISOString(),
                    'completed_at' => $assignment->completed_at?->toISOString(),
                ],

                'shift_info' => $assignment->courierShift ? [
                    'shift_id' => $assignment->courierShift->id,
                    'start_time' => $assignment->courierShift->start_time?->toISOString(),
                    'end_time' => $assignment->courierShift->expected_end_time?->toISOString(),
                    'is_open' => $assignment->courierShift->is_open,
                ] : null,
            ];

            return $this->successResponse($details, __('Order details retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get courier's earnings summary
     */
    public function earningsSummary(Request $request): JsonResponse
    {
        $courierId = auth('sanctum')->id();
        $period = $request->get('period', 'today'); // today, week, month

        try {
            $query = CourierOrderAssignment::where('courier_id', $courierId)
                ->where('status', 'delivered');

            // Apply period filter
            $query = $this->applyPeriodFilter($query, $period);

            $completedOrders = $query->get();

            $summary = [
                'period' => $period,
                'total_orders' => $completedOrders->count(),
                'total_earnings' => $completedOrders->sum('actual_earning'),
                'total_distance' => $completedOrders->sum('actual_distance_km'),
                'average_rating' => $completedOrders->avg('courier_rating'),
                'average_order_value' => $completedOrders->count() > 0
                    ? $completedOrders->sum('actual_earning') / $completedOrders->count()
                    : 0,

                'today_stats' => $this->getTodayStats($courierId),
                'recent_orders' => $completedOrders->take(5)->map(function ($assignment) {
                    return [
                        'order_id' => $assignment->order_id,
                        'completed_at' => $assignment->completed_at?->toISOString(),
                        'earning' => $assignment->actual_earning,
                        'rating' => $assignment->courier_rating,
                    ];
                }),
            ];

            return $this->successResponse($summary, __('Earnings summary retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get full order details with products and customer info
     */
    public function fullOrderDetails(Request $request, $assignmentId): JsonResponse
    {
        $courierId = auth('sanctum')->id();

        try {
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->with([
                    'order.user',
                    'order.store',
                    'order.orderItems.product.translations',
                    'order.orderItems.addOns.translations',
                    'order.deliveryAddress.area.translations',
                    'order.deliveryAddress.city.translations',
                    'order.deliveryAddress.governorate.translations',
                    'order.paymentMethod',
                    'receipts',
                ])
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('Assignment not found or access denied'), 404);
            }

            return $this->successResponse([
                'order' => new FullOrderResource($assignment)
            ], __('Order details retrieved successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Upload pickup receipt (product photo / bill photo)
     */
    public function uploadPickupReceipt(Request $request, $assignmentId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,jpg,png,gif|max:5120', // 5MB max
            'type' => 'required|in:pickup_product,pickup_receipt',
            'metadata' => 'nullable|array',
            'metadata.latitude' => 'nullable|numeric|between:-90,90',
            'metadata.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $courierId = auth('sanctum')->id();

        try {
            $receipt = $this->receiptService->uploadReceipt(
                $assignmentId,
                $request->file('file'),
                $request->type,
                $request->get('metadata', [])
            );

            return $this->successResponse([
                'receipt' => [
                    'id' => $receipt->id,
                    'file_name' => $receipt->file_name,
                    'url' => $receipt->url,
                    'file_size_human' => $receipt->file_size_human,
                    'type' => $receipt->type,
                    'uploaded_at' => $receipt->created_at->toISOString(),
                ]
            ], __('Receipt uploaded successfully'), 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Upload delivery proof (customer signature / delivery photo)
     */
    public function uploadDeliveryProof(Request $request, $assignmentId): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|image|mimes:jpeg,jpg,png,gif|max:5120', // 5MB max
            'type' => 'required|in:delivery_proof,customer_signature',
            'metadata' => 'nullable|array',
            'metadata.latitude' => 'nullable|numeric|between:-90,90',
            'metadata.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $courierId = auth('sanctum')->id();

        try {
            $receipt = $this->receiptService->uploadReceipt(
                $assignmentId,
                $request->file('file'),
                $request->type,
                $request->get('metadata', [])
            );

            return $this->successResponse([
                'receipt' => [
                    'id' => $receipt->id,
                    'file_name' => $receipt->file_name,
                    'url' => $receipt->url,
                    'file_size_human' => $receipt->file_size_human,
                    'type' => $receipt->type,
                    'uploaded_at' => $receipt->created_at->toISOString(),
                ]
            ], __('Delivery proof uploaded successfully'), 201);

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Delete an uploaded receipt
     */
    public function deleteReceipt(Request $request, $assignmentId, $receiptId): JsonResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $courierId = auth('sanctum')->id();

        try {
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('Assignment not found or access denied'), 404);
            }

            $receipt = $assignment->receipts()->findOrFail($receiptId);

            $deleted = $this->receiptService->deleteReceipt($receipt);

            if (!$deleted) {
                return $this->errorResponse(__('Failed to delete receipt'), 400);
            }

            return $this->successResponse([
                'receipt_id' => $receiptId,
                'deleted_at' => now()->toISOString(),
            ], __('Receipt deleted successfully'));

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    // Private helper methods

    private function findNearbyPendingOrders(float $lat, float $lng, float $radius, int $courierId): Collection
    {
        // In a real implementation, this would query for pending orders nearby
        // For now, we'll return an empty collection as placeholder
        return collect([]);
    }

    private function prepareMapMarkers(Collection $activeAssignments, Collection $pendingOrders): array
    {
        $markers = [];

        // Active assignments markers
        foreach ($activeAssignments as $assignment) {
            $markers[] = [
                'id' => "assignment_{$assignment->id}",
                'type' => 'active_assignment',
                'lat' => $assignment->pickup_lat,
                'lng' => $assignment->pickup_lng,
                'color' => $this->getAssignmentColor($assignment->status),
                'animation' => $assignment->status === 'assigned' ? 'pulse' : null,
                'title' => "Order #{$assignment->order_id}",
                'details' => [
                    'status' => $assignment->status,
                    'expires_in' => $assignment->time_remaining,
                    'distance' => $assignment->estimated_distance_km,
                    'earning' => $assignment->estimated_earning,
                ],
            ];

            // Add delivery point if different
            if ($assignment->delivery_lat && $assignment->delivery_lng) {
                $markers[] = [
                    'id' => "delivery_{$assignment->id}",
                    'type' => 'delivery_point',
                    'lat' => $assignment->delivery_lat,
                    'lng' => $assignment->delivery_lng,
                    'color' => 'red',
                    'icon' => '📦',
                ];
            }
        }

        return $markers;
    }

    private function getCourierStatusSummary(int $courierId, Collection $activeAssignments): array
    {
        $now = now();

        return [
            'active_orders' => $activeAssignments->count(),
            'pending_assignments' => $activeAssignments->where('status', 'assigned')->count(),
            'in_transit' => $activeAssignments->where('status', 'in_transit')->count(),
            'today_earnings' => $this->getTodayEarnings($courierId),
            'is_on_shift' => true, // This should be checked against actual shift status
        ];
    }

    private function getTodayEarnings(int $courierId): float
    {
        return CourierOrderAssignment::where('courier_id', $courierId)
            ->where('status', 'delivered')
            ->whereDate('completed_at', today())
            ->sum('actual_earning');
    }

    private function getTodayStats(int $courierId): array
    {
        $today = today();

        return [
            'orders_completed' => CourierOrderAssignment::where('courier_id', $courierId)
                ->where('status', 'delivered')
                ->whereDate('completed_at', $today)
                ->count(),
            'orders_failed' => CourierOrderAssignment::where('courier_id', $courierId)
                ->where('status', 'failed')
                ->whereDate('completed_at', $today)
                ->count(),
            'total_distance' => CourierOrderAssignment::where('courier_id', $courierId)
                ->whereIn('status', ['delivered', 'failed'])
                ->whereDate('completed_at', $today)
                ->sum('actual_distance_km'),
        ];
    }

    private function calculateOptimalZoom(float $radius): int
    {
        // Rough zoom calculation based on radius
        if ($radius <= 1) return 15;
        if ($radius <= 2) return 14;
        if ($radius <= 5) return 13;
        if ($radius <= 10) return 11;
        return 10;
    }

    private function getAssignmentColor(string $status): string
    {
        return match($status) {
            'assigned' => 'blue',
            'accepted' => 'orange',
            'in_transit' => 'yellow',
            'delivered' => 'green',
            'failed' => 'red',
            default => 'gray'
        };
    }

    private function isValidStatusTransition(string $currentStatus, string $newStatus): bool
    {
        $transitions = [
            'assigned' => ['accepted'],
            'accepted' => ['in_transit'],
            'in_transit' => ['delivered', 'failed'],
        ];

        return isset($transitions[$currentStatus]) && in_array($newStatus, $transitions[$currentStatus]);
    }

    private function applyPeriodFilter($query, string $period)
    {
        return match($period) {
            'today' => $query->whereDate('completed_at', today()),
            'week' => $query->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year),
            default => $query->whereDate('completed_at', today()),
        };
    }
}
