<?php

namespace Modules\Couier\Http\Controllers;

use Carbon\Carbon;
use App\Enums\OrderStatus;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Order\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use Modules\Couier\Services\OrderReceiptService;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Order\Events\OrderAssignmentToCourier;
use Modules\Couier\Http\Resources\FullOrderResource;
use Modules\Couier\Services\GeographicCourierService;
use Modules\Couier\Http\Resources\OrderReceiptResource;
use Modules\Couier\Services\SmartOrderAssignmentService;

class CourierMapController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SmartOrderAssignmentService $assignmentService,
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
    public function respondToAssignment(Request $request, $assignmentId)
    {
        $request->validate([
            'action' => 'required|in:accept,reject',
            'reason' => 'nullable|string|max:255'
        ]);

        $courierId = auth('sanctum')->id();
        try {
            if ($request->action == 'accept') {
                $success = $this->assignmentService->acceptAssignment($assignmentId, $courierId);
                $message = $success ? __('Order accepted successfully') : __('Failed to accept order');
            } else {
                $success = $this->assignmentService->rejectAssignment($assignmentId, $courierId, $request->reason);
                $message = $success ? __('Order rejected') : __('Failed to reject order');
            }

            $statusCode = $success ? 200 : 400;

            return $this->successResponse([
                'assignment_id' => $assignmentId,
                'action' => $request->action,
                'success' => $success,
            ], $message, $statusCode);
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
            'location_lat' => 'string',
            'location_lng' => 'string',
            'failure_reason' => 'required_if:status,failed|string|max:500',
            'receipt_file' => 'nullable|file|image|mimes:jpeg,jpg,png,gif|max:5120',
            'receipt_type' => 'required_with:receipt_file|in:pickup_product,pickup_receipt,delivery_proof,customer_signature',
            'metadata' => 'nullable|array',
            'metadata.latitude' => 'nullable|string',
            'metadata.longitude' => 'nullable|string',
        ]);

        $courierId = auth('sanctum')->id();

        try {
            // Verify the assignment belongs to this courier
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('message.assignment_not_found'), 404);
            }

            // Validate status transition
            if (!$this->isValidStatusTransition($assignment->status, $request->status)) {
                return $this->errorResponse(__('message.invalid_status_transition', ['current' => 'unknown', 'allowed' => 'check logic']), 422);
            }

            // Validate receipt type for current status if receipt is being uploaded
            if ($request->hasFile('receipt_file')) {
                $this->validateReceiptTypeForStatus($request->receipt_type, $request->status);
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
                return $this->errorResponse(__('message.failed_to_update_status'), 400);
            }

            // Handle receipt upload if file is provided
            $uploadedReceipt = null;
            if ($request->hasFile('receipt_file')) {
                $uploadedReceipt = $this->receiptService->uploadReceipt(
                    $assignmentId,
                    $request->file('receipt_file'),
                    $request->receipt_type,
                    $request->get('metadata', [])
                );
            }

            $responseData = [
                'assignment_id' => $assignmentId,
                'new_status' => $request->status,
                'updated_at' => now()->toISOString(),
            ];

            // Add receipt information to response if receipt was uploaded
            if ($uploadedReceipt) {
                $responseData['receipt'] = [
                    'id' => $uploadedReceipt->id,
                    'file_name' => $uploadedReceipt->file_name,
                    'url' => $uploadedReceipt->url,
                    'file_size_human' => $uploadedReceipt->file_size_human,
                    'type' => $uploadedReceipt->type,
                    'uploaded_at' => $uploadedReceipt->created_at->toISOString(),
                ];
            }

            return $this->successResponse($responseData, __('Order status updated successfully'));
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
        return match ($status) {
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

    private function validateReceiptTypeForStatus(string $type, string $status): void
    {
        $allowedTypes = match ($status) {
            'accepted' => ['pickup_product', 'pickup_receipt'],
            'in_transit' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            'delivered', 'failed' => ['pickup_product', 'pickup_receipt', 'delivery_proof', 'customer_signature'],
            default => []
        };

        if (!in_array($type, $allowedTypes)) {
            throw new \InvalidArgumentException("Receipt type '{$type}' is not allowed for status '{$status}'");
        }
    }

    private function applyPeriodFilter($query, string $period)
    {
        return match ($period) {
            'today' => $query->whereDate('completed_at', today()),
            'week' => $query->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereMonth('completed_at', now()->month)->whereYear('completed_at', now()->year),
            default => $query->whereDate('completed_at', today()),
        };
    }

    /**
     * Get comprehensive order details for courier mobile app
     */
    public function comprehensiveOrderDetails($assignmentId): JsonResponse
    {
        $courierId = auth('sanctum')->id();

        try {
            $assignment = CourierOrderAssignment::where('id', $assignmentId)
                ->where('courier_id', $courierId)
                ->with([
                    'order.user',
                    'order.store',
                    'order.deliveryAddress',
                    'order.orderItems',
                    'receipts',
                ])
                ->first();

            if (!$assignment) {
                return $this->errorResponse(__('Assignment not found or access denied'), 404);
            }

            $courier = auth('sanctum')->user();

            return $this->successResponse([
                'courier' => [
                    'name' => $courier->first_name . ' ' . $courier->last_name,
                    'avatar' => $courier->avatar,
                    'status' => $this->getCourierStatus($assignment),
                    'status_description' => $this->getCourierStatusDescription($assignment),
                ],
                'order' => [
                    'order_number' => '#' . $assignment->order_id . ' - ' . $assignment->order->user->phone,
                    'items_count' => $assignment->order->orderItems->count(),
                    'delivery_address' => [
                        'full_address' => $this->formatFullAddress($assignment->order->deliveryAddress),
                        'street_address' => $assignment->order->deliveryAddress->street_address ?? '',
                    ],
                    'payments' => [
                        'pay_to_seller' => $this->calculateAmountToSeller($assignment->order),
                        'collect_from_customer' => $assignment->order->total_amount,
                        'seller_payment_method' => 'كاش',
                        'customer_payment_method' => 'كاش',
                    ],
                    'instructions' => [
                        'pickup_receipt' => [
                            'required' => true,
                            'description' => 'التقط صورة للفاتورة مع الطلب',
                            'instructions' => 'يرجى تصوير الفاتورة بجانب الطلب للتأكد من صحة العناصر ومراجعة التفاصيل قبل بدء التوصيل.',
                            'uploaded' => $this->hasReceiptType($assignment, 'pickup_receipt'),
                            'image_url' => $this->getReceiptImage($assignment, 'pickup_receipt'),
                        ],
                        'delivery_proof' => [
                            'required' => true,
                            'description' => 'التقط صورة لإثبات التسليم',
                            'instructions' => 'يرجى تصوير الطلب عند تسليمه للعميل للتأكيد على إتمام العملية بنجاح وتوثيق حالة الطلب.',
                            'uploaded' => $this->hasReceiptType($assignment, 'delivery_proof'),
                            'image_url' => $this->getReceiptImage($assignment, 'delivery_proof'),
                            'can_retake' => true,
                        ],
                    ],
                ],
                'progress' => [
                    'steps' => $this->getProgressSteps($assignment),
                ],
            ], __('Order details retrieved successfully'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    private function getCourierStatus(CourierOrderAssignment $assignment): string
    {
        $now = now();

        // Check if courier is late
        if ($assignment->expected_completion_at && $now->isAfter($assignment->expected_completion_at)) {
            $minutesLate = $now->diffInMinutes($assignment->expected_completion_at);
            return "متأخر {$minutesLate} دقيقة";
        }

        // Return status based on assignment status
        return match ($assignment->status) {
            'assigned' => 'تم تعيين الطلب',
            'accepted' => 'في الطريق للمتجر',
            'in_transit' => 'في الطريق للعميل',
            'delivered' => 'تم التسليم',
            'failed' => 'فشل التسليم',
            default => 'قيد المعالجة',
        };
    }

    private function getCourierStatusDescription(CourierOrderAssignment $assignment): string
    {
        return match ($assignment->status) {
            'assigned' => 'بانتظار قبول الطلب',
            'accepted' => 'جاري استلام الطلب من المتجر',
            'in_transit' => 'في الطريق لتسليم الطلب',
            'delivered' => 'تم تسليم الطلب بنجاح',
            'failed' => 'فشل في تسليم الطلب',
            default => 'جاري معالجة الطلب',
        };
    }

    private function formatFullAddress($address): string
    {
        if (!$address) return '';

        $parts = [];
        if ($address->country) $parts[] = $address->country->name;
        if ($address->governorate) $parts[] = $address->governorate->name;
        if ($address->city) $parts[] = $address->city->name;

        return implode(', ', $parts);
    }

    private function calculateAmountToSeller($order): float
    {
        // Calculate amount to pay to seller (order total minus courier fee and other fees)
        // This is a simplified calculation - adjust based on your business logic
        $courierFee = 15.00; // Example courier fee
        return max(0, $order->total_amount - $courierFee);
    }

    private function hasReceiptType(CourierOrderAssignment $assignment, string $type): bool
    {
        return $assignment->receipts->where('type', $type)->isNotEmpty();
    }

    private function getReceiptImage(CourierOrderAssignment $assignment, string $type)
    {
        $receipt = $assignment->receipts->where('type', $type)->first();
        return $receipt ? $receipt->url : null;
    }

    private function getProgressSteps(CourierOrderAssignment $assignment): array
    {
        $steps = [
            [
                'id' => 'pickup',
                'title' => 'التقط صورة للفاتورة',
                'completed' => $this->hasReceiptType($assignment, 'pickup_receipt'),
                'current' => $assignment->status === 'accepted' && !$this->hasReceiptType($assignment, 'pickup_receipt'),
            ],
            [
                'id' => 'transit',
                'title' => 'توصيل الطلب',
                'completed' => in_array($assignment->status, ['in_transit', 'delivered', 'failed']),
                'current' => $assignment->status === 'in_transit',
            ],
            [
                'id' => 'proof',
                'title' => 'إثبات التسليم',
                'completed' => in_array($assignment->status, ['delivered', 'failed']),
                'current' => $assignment->status === 'in_transit' && !$this->hasReceiptType($assignment, 'delivery_proof'),
            ],
        ];

        return $steps;
    }
}
