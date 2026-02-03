<?php

namespace Modules\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Order\Events\OrderStatusChanged;
use App\Enums\OrderStatus;
use Modules\Couier\Services\CacheBasedOrderAssignmentService;
use Modules\Couier\Services\RealTimeCourierService;
use Illuminate\Support\Facades\Log;

/**
 * Listener for automatically assigning couriers to orders when they become ready for delivery
 *
 * This listener is triggered whenever an order status changes to READY_FOR_DELIVERY.
 * It will attempt to find and assign the nearest available courier to deliver the order.
 */
class AutoAssignCourierListener implements ShouldQueue
{


    public function __construct(
        protected CacheBasedOrderAssignmentService $assignmentService,
        protected RealTimeCourierService $realtimeService
    ) {}

    /**
     * Handle the order status changed event
     *
     * @param OrderStatusChanged $event The triggered event containing order details
     * @return void
     *
     * @throws \Exception When assignment operation fails critically
     */
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->newStatus->value != 'ready_for_delivery') {
            return;
        }

        if (!$this->isOrderEligibleForAssignment($event->order)) {
            Log::info('Order skipped for auto-assignment', [
                'order_id' => $event->order->id,
                'reason' => 'Order already has assigned courier or ineligible'
            ]);
            return;
        }
        $this->processAutoAssignment($event);
    }

    /**
     * Check if order is eligible for automatic courier assignment
     *
     * @param \Modules\Order\Models\Order $order
     * @return bool
     */
    private function isOrderEligibleForAssignment($order): bool
    {
        // Check if order already has a courier assigned - use correct column name 'couier_id'
        if ($order->couier_id) {
            return false;
        }

        // Check if order has required location data
        // Check relationships first to avoid null property access
        if (!$order->store || !$order->store->address || !$order->deliveryAddress) {
            Log::warning('Order missing relationship data for auto-assignment', [
                'order_id' => $order->id,
            ]);
            return false;
        }

        $hasPickupLocation = $order->store->address->latitude;
        $hasDeliveryLocation = $order->deliveryAddress->latitude;

        if (!$hasPickupLocation || !$hasDeliveryLocation) {
            Log::warning('Order missing required location data for auto-assignment', [
                'order_id' => $order->id,
                'has_pickup' => $hasPickupLocation,
                'has_delivery' => $hasDeliveryLocation,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Process the automatic courier assignment
     *
     * @param OrderStatusChanged $event
     * @return void
     */
    private function processAutoAssignment(OrderStatusChanged $event): void
    {
        try {
            $orderData = $this->prepareOrderDataForAssignment($event->order);
            // Use injected assignment service
            $assignment = $this->assignmentService->assignOrderToNearestCourier($orderData, $event->order);

            if ($assignment) {
                Log::info("handleSuccessfulAssignment");
                $this->handleSuccessfulAssignment($event->order, $assignment);
                Log::info('Cache-based assignment successful for order: ' . $event->order->id);
            } else {
                Log::info("handleAssignmentFailure");
                $this->handleAssignmentFailure($event->order);
                Log::info('Cache-based assignment failed for order: ' . $event->order->id);
            }
        } catch (\Exception $e) {
            Log::error('Cache-based assignment failed for order: ' . $event->order->id . ' - ' . $e->getMessage());
            $this->handleAssignmentError($event->order, exception: $e);
        }
    }


    /**
     * Prepare order data structure required for assignment service
     *
     * @param \Modules\Order\Models\Order $order
     * @return array
     */
    private function prepareOrderDataForAssignment($order): array
    {
        return [
            'order_id' => $order->id,
            'pickup_lat' => $order->store->address->latitude,
            'pickup_lng' => $order->store->address->longitude,
            'delivery_lat' => $order->deliveryAddress->latitude,
            'delivery_lng' => $order->deliveryAddress->longitude,
            'priority_level' => 'normal',
        ];
    }

    /**
     * Handle successful courier assignment
     *
     * @param \Modules\Order\Models\Order $order
     * @param \Modules\Couier\Models\CourierOrderAssignment $assignment
     * @return void
     */
    private function handleSuccessfulAssignment($order, $assignment): void
    {
        // Order update is now handled atomically within the service
        // $order->update(['couier_id' => $assignment->courier_id]);

        // Log successful assignment
        Log::info('Courier automatically assigned to order', [
            'order_id' => $order->id,
            'courier_id' => $assignment->courier_id,
            'assignment_id' => $assignment->id,
            'estimated_distance' => $assignment->estimated_distance_km,
        ]);

        // Real-time notification is now handled by CacheBasedOrderAssignmentService
        // to avoid duplicate event dispatching
    }

    /**
     * Handle assignment failure (no suitable couriers found)
     *
     * @param \Modules\Order\Models\Order $order
     * @return void
     */
    private function handleAssignmentFailure($order): void
    {
        Log::warning('Auto-assignment failed: No suitable couriers found', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'store_id' => $order->store_id,
        ]);

        // Here you could:
        // 1. Send notification to admin about assignment failure
        // 2. Queue for manual assignment
        // 3. Implement fallback assignment logic
    }

    /**
     * Handle assignment processing errors
     *
     * @param \Modules\Order\Models\Order $order
     * @param \Exception $exception
     * @return void
     */
    private function handleAssignmentError($order, \Exception $exception): void
    {
        Log::error('Auto-assignment critical error', [
            'order_id' => $order->id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);

        // For critical errors, you might want to:
        // 1. Send alerts to development team
        // 2. Flag order for manual review
        // 3. Retry logic or circuit breaker pattern
    }

    /**
     * Define the retry logic and delay for the job
     *
     * @return \Carbon\Carbon
     */
    public function retryUntil(): \Carbon\Carbon
    {
        // Give assignment operation 5 minutes to complete
        return now()->addMinutes(5);
    }
}
