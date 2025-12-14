<?php

namespace Modules\Couier\Services;

use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Couier\Models\Couier;
use Modules\Couier\Models\CouierShift;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class SmartOrderAssignmentService
{
    protected RealTimeCourierService $realtimeService;
    protected GeographicCourierService $geographicService;

    public function __construct(
        RealTimeCourierService $realtimeService,
        GeographicCourierService $geographicService
    ) {
        $this->realtimeService = $realtimeService;
        $this->geographicService = $geographicService;
    }

    /**
     * Auto assign order to nearest available courier
     */
    public function autoAssignOrder(
        array $orderData,
        int $timeoutSeconds = 120,
        array $criteria = []
    ): ?CourierOrderAssignment {
        DB::beginTransaction();

        try {
            // Extract order locations
            $pickupLat = $orderData['pickup_lat'] ?? null;
            $pickupLng = $orderData['pickup_lng'] ?? null;
            $deliveryLat = $orderData['delivery_lat'] ?? null;
            $deliveryLng = $orderData['delivery_lng'] ?? null;

            if (!$pickupLat || !$pickupLng) {
                throw new Exception('Pickup location is required');
            }

            // Find optimal couriers
            $criteria = array_merge($criteria, [
                'priority' => $criteria['priority'] ?? 'balanced', // distance, rating, load
                'max_results' => $criteria['max_results'] ?? 5,
                'radius_km' => $criteria['radius_km'] ?? 5.0,
            ]);

            $optimalCouriers = $this->geographicService->findOptimalCouriers(
                $pickupLat,
                $pickupLng,
                $deliveryLat,
                $deliveryLng,
                $criteria
            );

            if ($optimalCouriers->isEmpty()) {
                throw new Exception('No available couriers found nearby');
            }

            // Try to assign to couriers in order until one accepts
            return $this->assignToBestCourier($orderData, $optimalCouriers, $timeoutSeconds);

        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to auto assign order', [
                'order_data' => $orderData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Assign order to specific courier with timeout
     */
    public function assignToCourier(
        int $courierId,
        array $orderData,
        int $timeoutSeconds = 120
    ): ?CourierOrderAssignment {
        DB::beginTransaction();

        try {
            $courier = Couier::findOrFail($courierId);

            // Verify courier can take assignments
            if (!$this->canCourierAcceptAssignment($courier)) {
                throw new Exception('Courier is not available for assignments');
            }

            // Create assignment with timeout
            $assignment = $this->createAssignment($courierId, $orderData, $timeoutSeconds);

            // Send real-time notification
            $this->realtimeService->notifyOrderAssigned($courierId, $assignment);

            // Schedule timeout handler
            $this->scheduleTimeoutHandling($assignment->id, $timeoutSeconds);

            DB::commit();
            return $assignment;

        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to assign order to courier', [
                'courier_id' => $courierId,
                'order_data' => $orderData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Courier accepts assignment
     */
    public function acceptAssignment(int $assignmentId, int $courierId): bool
    {
        $assignment = CourierOrderAssignment::where('id', $assignmentId)
            ->where('courier_id', $courierId)
            ->where('status', 'assigned')
            ->first();

        if (!$assignment || $assignment->is_expired) {
            return false;
        }

        DB::beginTransaction();

        try {
            $assignment->accept();
            $assignment->save();

            // Notify relevant parties
            \Log::info('Assignment accepted for order: ' . $assignment->order_id . ' - ' . $courierId);
            $this->realtimeService->notifyOrderStatusChanged(
                $assignment->order_id,
                'accepted',
                ['courier_id' => $courierId]
            );

            // Cancel notification for other couriers
            \Log::info('Assignment accepted for order: ' . $assignment->order_id . ' - ' . $courierId);
            $this->realtimeService->notifyOrderStatusChanged(
                $assignment->order_id,
                'assignment_finalized',
                ['accepted_courier_id' => $courierId]
            );
            \Log::info('Assignment finalized for order: ' . $assignment->order_id . ' - ' . $courierId);
            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to accept assignment', [
                'assignment_id' => $assignmentId,
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Courier rejects assignment
     */
    public function rejectAssignment(int $assignmentId, int $courierId, string $reason = null): bool
    {
        $assignment = CourierOrderAssignment::where('id', $assignmentId)
            ->where('courier_id', $courierId)
            ->where('status', 'assigned')
            ->first();

        if (!$assignment) {
            return false;
        }

        try {
            $assignment->reject($reason);
            $assignment->save();

            // If this rejection causes the order to fail, notify system
            if ($this->shouldReassignOrder($assignment->order_id)) {
                $this->tryReassignment($assignment->order_id, $assignmentId);
            }

            return true;

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to reject assignment', [
                'assignment_id' => $assignmentId,
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Handle assignment timeout
     */
    public function handleTimeout(int $assignmentId): void
    {
        $assignment = CourierOrderAssignment::find($assignmentId);

        if (!$assignment || $assignment->status !== 'assigned') {
            return;
        }

        try {
            $assignment->update([
                'status' => 'timed_out',
                'completed_at' => now()
            ]);

            // Notify the courier
            $this->realtimeService->notifyOrderExpired(
                $assignment->courier_id,
                $assignment->order_id,
                'timeout'
            );

            // Try to reassign
            if ($this->shouldReassignOrder($assignment->order_id)) {
                $this->tryReassignment($assignment->order_id, $assignmentId);
            }

        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to handle assignment timeout', [
                'assignment_id' => $assignmentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Update assignment status
     */
    public function updateAssignmentStatus(
        int $assignmentId,
        string $newStatus,
        array $additionalData = []
    ): bool {
        $assignment = CourierOrderAssignment::find($assignmentId);

        if (!$assignment) {
            return false;
        }

        DB::beginTransaction();

        try {
            $updateData = ['status' => $newStatus];

            // Set appropriate timestamps based on status
            switch ($newStatus) {
                case 'accepted':
                    if ($assignment->canBeAccepted()) {
                        $updateData['accepted_at'] = now();
                        // $assignment->accept() was already called, so we don't need to call it again
                    } else {
                        throw new Exception('Cannot accept this assignment');
                    }
                    break;

                case 'in_transit':
                    if ($assignment->status === 'accepted') {
                        $updateData['started_at'] = now();
                    }
                    break;

                case 'delivered':
                    $updateData['completed_at'] = now();
                    break;

                case 'failed':
                    $updateData['completed_at'] = now();
                    $updateData['rejection_reason'] = $additionalData['reason'] ?? 'Delivery failed';
                    break;
            }

            $assignment->update($updateData);
            \Log::info('Assignment status updated for order: ' . $assignment->order_id . ' - ' . $newStatus);
            // Real-time notification
            $this->realtimeService->notifyOrderStatusChanged(
                $assignment->order_id,
                $newStatus,
                array_merge($additionalData, [
                    'courier_id' => $assignment->courier_id,
                    'assignment_id' => $assignmentId
                ])
            );

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Failed to update assignment status', [
                'assignment_id' => $assignmentId,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get courier's active assignments
     */
    public function getCourierActiveAssignments(int $courierId): Collection
    {
        return CourierOrderAssignment::where('courier_id', $courierId)
            ->active()
            ->with('order')
            ->get();
    }

    /**
     * Update courier location
     */
    public function updateCourierLocation(int $courierId, float $lat, float $lng): bool
    {
        if ($this->geographicService->updateCourierLocation($courierId, $lat, $lng)) {
            // Notify subscribers about location update
            $this->realtimeService->updateCourierLocation($courierId, $lat, $lng);

            // Check if courier now qualifies for pending orders
            $this->checkPendingOrdersForCourier($courierId, $lat, $lng);

            return true;
        }
        return false;
    }

    // Private helper methods

    /**
     * Assign to best available courier from the list
     */
    private function assignToBestCourier(array $orderData, Collection $couriers, int $timeoutSeconds): CourierOrderAssignment
    {
        foreach ($couriers as $courier) {
            $assignment = $this->assignToCourier($courier->id, $orderData, $timeoutSeconds);
            if ($assignment) {
                return $assignment;
            }
        }

        throw new Exception('Failed to assign to any courier');
    }

    /**
     * Create assignment record
     */
    private function createAssignment(int $courierId, array $orderData, int $timeoutSeconds): CourierOrderAssignment
    {
        return CourierOrderAssignment::create([
            'courier_id' => $courierId,
            'order_id' => $orderData['order_id'],
            'courier_shift_id' => $orderData['courier_shift_id'] ?? null,
            'pickup_lat' => $orderData['pickup_lat'],
            'pickup_lng' => $orderData['pickup_lng'],
            'delivery_lat' => $orderData['delivery_lat'],
            'delivery_lng' => $orderData['delivery_lng'],
            'estimated_distance_km' => $this->calculateEstimatedDistance($orderData),
            'priority_level' => $orderData['priority_level'] ?? 'normal',
            'expires_at' => now()->addSeconds($timeoutSeconds),
            'status' => 'assigned',
        ]);
    }

    /**
     * Calculate estimated distance for order
     */
    private function calculateEstimatedDistance(array $orderData): ?float
    {
        if (isset($orderData['pickup_lat'], $orderData['pickup_lng'], $orderData['delivery_lat'], $orderData['delivery_lng'])) {
            return $this->geographicService->calculateDistance(
                $orderData['pickup_lat'],
                $orderData['pickup_lng'],
                $orderData['delivery_lat'],
                $orderData['delivery_lng']
            );
        }
        return null;
    }

    /**
     * Check if courier can accept assignments
     */
    private function canCourierAcceptAssignment(Couier $courier): bool
    {
        return $courier->status->value === 'active'
            && $courier->avaliable_status->value === 'available'
            && $courier->shifts()->where('is_open', true)->whereNull('end_time')->exists(); // Must be on active shift
    }

    /**
     * Schedule timeout handling
     */
    private function scheduleTimeoutHandling(int $assignmentId, int $timeoutSeconds): void
    {
        // Schedule a job to handle assignment timeout
        \App\Jobs\CourierAssignmentTimeoutJob::dispatch($assignmentId)
            ->delay(now()->addSeconds($timeoutSeconds));
    }

    /**
     * Check if order should be reassigned after rejection/timeout
     */
    private function shouldReassignOrder(int $orderId): bool
    {
        $activeAssignments = CourierOrderAssignment::where('order_id', $orderId)
            ->active()
            ->count();

        return $activeAssignments === 0; // Reassign only if no active assignments
    }

    /**
     * Try to reassign order
     */
    private function tryReassignment(int $orderId, int $excludedAssignmentId = null): void
    {
        $assignment = CourierOrderAssignment::where('order_id', $orderId)->first();

        if (!$assignment) return;

        $orderData = [
            'order_id' => $orderId,
            'pickup_lat' => $assignment->pickup_lat,
            'pickup_lng' => $assignment->pickup_lng,
            'delivery_lat' => $assignment->delivery_lat,
            'delivery_lng' => $assignment->delivery_lng,
            'priority_level' => $assignment->priority_level,
        ];

        $this->autoAssignOrder($orderData);
    }

    /**
     * Check if courier's new location qualifies them for pending orders
     */
    private function checkPendingOrdersForCourier(int $courierId, float $lat, float $lng): void
    {
        try {
            // Check pending orders (ready_for_delivery without active assignments)
            $pendingOrders = \Modules\Order\Models\Order::where('status', 'ready_for_delivery')
                ->whereDoesntHave('courier') // No assigned courier
                ->with(['deliveryAddress', 'store.address'])
                ->get();

            foreach ($pendingOrders as $order) {
                $deliveryLat = $order->deliveryAddress?->latitude;
                $deliveryLng = $order->deliveryAddress?->longitude;
                $storeLat = $order->store->address?->latitude;
                $storeLng = $order->store->address?->longitude;

                if ($deliveryLat && $deliveryLng && $storeLat && $storeLng) {
                    // Check if courier is near the pickup location
                    $pickupDistance = $this->geographicService->calculateDistance(
                        $lat, $lng,
                        $storeLat, $storeLng
                    );

                    // If within 3km and courier is available, try assignment
                    if ($pickupDistance <= 3.0) {
                        $this->autoAssignOrder([
                            'order_id' => $order->id,
                            'pickup_lat' => $storeLat,
                            'pickup_lng' => $storeLng,
                            'delivery_lat' => $deliveryLat,
                            'delivery_lng' => $deliveryLng,
                            'priority_level' => 'high', // Quick assignments for nearby orders
                        ], 60); // Shorter timeout for nearby assignments

                        \Illuminate\Support\Facades\Log::info('Assigned pending order to nearby courier', [
                            'order_id' => $order->id,
                            'courier_id' => $courierId,
                            'distance_km' => $pickupDistance,
                        ]);
                    }
                }
            }

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to check pending orders for courier', [
                'courier_id' => $courierId,
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage()
            ]);
        }
    }
}
