<?php

namespace Modules\Couier\Services;

use Pusher\Pusher;
use Illuminate\Support\Facades\Log;
use Modules\Couier\Events\NewOrderAssigned;
use Modules\Couier\Events\OrderStatusChanged;
use Modules\Couier\Events\OrderAssignedExpired;
use Modules\Couier\Events\OrderAcceptedByCourier;
use Modules\Couier\Events\OrderAssignedToCourier;
use Modules\Couier\Models\CourierOrderAssignment;

class RealTimeCourierService
{
    protected Pusher $pusher;

    public function __construct()
    {
        $this->pusher = new Pusher(
            config('broadcasting.connections.pusher.key'),
            config('broadcasting.connections.pusher.secret'),
            config('broadcasting.connections.pusher.app_id'),
            [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'host' => config('broadcasting.connections.pusher.options.host', 'api.pusherapp.com'),
                'port' => config('broadcasting.connections.pusher.options.port', 443),
                'scheme' => config('broadcasting.connections.pusher.options.scheme', 'https'),
                'encrypted' => true,
                'useTLS' => config('broadcasting.connections.pusher.options.useTLS', true),
            ]
        );
    }

    /**
     * Send new order assignment to courier
     */
    public function notifyOrderAssigned(int $courierId, ?CourierOrderAssignment $assignment): void
    {
        // If assignment is null, log and return early
        if (!$assignment) {
            Log::warning("Attempted to notify null assignment", [
                'courier_id' => $courierId
            ]);
            return;
        }

        try {
            event(new NewOrderAssigned( $assignment,$courierId));

            Log::info("Pusher: New order assigned to courier", [
                'courier_id' => $courierId,
                'order_id' => $assignment->order_id
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send order assignment notification", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify courier that order is about to expire
     */
    public function notifyOrderExpiring(int $courierId, int $orderId, int $secondsLeft): void
    {
        try {
            // event(new OrderAssignedExpired($courierId, $orderId, $secondsLeft));

            Log::info("Pusher: Order expiring notification sent", [
                'courier_id' => $courierId,
                'order_id' => $orderId,
                'seconds_left' => $secondsLeft
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send expiring notification", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify that order assignment has expired
     */
    public function notifyOrderExpired(int $courierId, int $orderId, string $reason = 'timeout'): void
    {

        try {
            event(new OrderAssignedExpired($courierId, $orderId, $reason, now()->toISOString()));
            // $this->pusher->trigger("couriers.{$courierId}", "order-expired", [
            //     'order_id' => $orderId,
            //     'reason' => $reason,
            //     'expired_at' => now()->toISOString(),
            // ]);

            Log::info("Pusher: Order expired notification sent", [
                'courier_id' => $courierId,
                'order_id' => $orderId,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send expired notification", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify courier location updates for real-time tracking (Enhanced with caching)
     */
    public function updateCourierLocation(int $courierId, float $lat, float $lng, array $metadata = []): void
    {
        try {
            $locationData = [
                'lat' => $lat,
                'lng' => $lng,
                'timestamp' => now()->toISOString(),
                'accuracy' => $metadata['accuracy'] ?? null,
                'speed' => $metadata['speed'] ?? null,
                'heading' => $metadata['heading'] ?? null,
            ];

            // Store in cache for real-time operations
            \Illuminate\Support\Facades\Cache::put("courier_location:{$courierId}", $locationData, 3600);

            // Append to shift location history (if courier is on shift)
            $this->appendLocationToShiftCache($courierId, $locationData);

            // Broadcast to subscribers
            $this->pusher->trigger("couriers", "location-update.{$courierId}", $locationData);

            Log::info("Courier location cached and broadcasted", [
                'courier_id' => $courierId,
                'lat' => $lat,
                'lng' => $lng
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to cache courier location", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Append location to current shift's cache
     */
    private function appendLocationToShiftCache(int $courierId, array $locationData): void
    {
        // Get current shift for courier
        $currentShift = \Modules\Couier\Models\CouierShift::where('couier_id', $courierId)
            ->where('is_open', true)
            ->whereNull('end_time')
            ->first();

        if (!$currentShift) {
            return; // Courier not on active shift
        }

        $cacheKey = "shift_locations:{$courierId}:{$currentShift->id}";

        // Get existing locations or initialize empty array
        $locations = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        // Add new location
        $locations[] = $locationData;

        // Keep only last 1000 locations to prevent memory issues
        if (count($locations) > 1000) {
            $locations = array_slice($locations, -1000);
        }

        // Store with extended TTL (24 hours for long shifts)
        \Illuminate\Support\Facades\Cache::put($cacheKey, $locations, now()->addHours(24));
    }

    /**
     * Notify order status changes to order.{orderId} channel
     */
    public function notifyOrderStatusChanged(int $orderId, string $newStatus, array $additionalData = []): void
    {
        try {
            $data = array_merge([
                'order_id' => $orderId,
                'status' => $newStatus,
                'event' => 'status_changed',
                'changed_at' => now()->toISOString(),
            ], $additionalData);

            $this->pusher->trigger("order.{$orderId}", "status_changed", $data);

            Log::info("Pusher: Order status changed on order.{$orderId}", [
                'order_id' => $orderId,
                'new_status' => $newStatus
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send status change notification", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify order assignment to order.{orderId} channel
     */
    public function notifyOrderAssignedToOrder(int $orderId, array $assignmentData): void
    {
        try {
            $data = array_merge([
                'order_id' => $orderId,
                'event' => 'assigned',
                'assigned_at' => now()->toISOString(),
            ], $assignmentData);

            $this->pusher->trigger("order.{$orderId}", "assigned", $data);

            Log::info("Pusher: Order assigned on order.{$orderId}", [
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send assignment notification", [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify order updates to order.{orderId} channel
     */
    public function notifyOrderUpdate(int $orderId, string $event, array $data = []): void
    {
        try {
            $payload = array_merge([
                'order_id' => $orderId,
                'event' => $event,
                'timestamp' => now()->toISOString(),
            ], $data);

            $this->pusher->trigger("order.{$orderId}", $event, $payload);

            Log::info("Pusher: Order {$event} on order.{$orderId}", [
                'order_id' => $orderId,
                'event' => $event
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send order update", [
                'order_id' => $orderId,
                'event' => $event,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify admin about system updates
     */
    public function notifyAdminSystemUpdate(array $data): void
    {
        try {
            $this->pusher->trigger("admin", "system-activity-update", array_merge($data, [
                'timestamp' => now()->toISOString()
            ]));

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send admin notification", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify when order is reassigned to another courier
     */
    public function notifyOrderReassigned(int $oldCourierId, int $newCourierId, int $orderId): void
    {
        try {
            // Notify old courier
            $this->pusher->trigger("couriers", "order-reassigned.{$oldCourierId}", [
                'order_id' => $orderId,
                'action' => 'removed',
                'reason' => 'reassigned_to_another_courier',
            ]);

            // Notify new courier (will get full assignment data separately)
            $this->pusher->trigger("couriers", "order-reassigned.{$newCourierId}", [
                'order_id' => $orderId,
                'action' => 'assigned',
                'reason' => 'reassigned_from_another_courier',
            ]);

            Log::info("Pusher: Order reassigned", [
                'old_courier_id' => $oldCourierId,
                'new_courier_id' => $newCourierId,
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send reassignment notifications", [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Bulk notification for multiple orders (useful for map views)
     */
    public function notifyCourierOrderUpdates(int $courierId, array $orderUpdates): void
    {
        try {
            $this->pusher->trigger("couriers", "orders-batch-update.{$courierId}", [
                'updates' => $orderUpdates,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send batch updates", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Emergency broadcast to all couriers (system alerts)
     */
    public function broadcastEmergency(array $emergencyData): void
    {
        try {
            $this->pusher->trigger("couriers", "system-emergency", array_merge($emergencyData, [
                'timestamp' => now()->toISOString(),
                'level' => 'emergency'
            ]));

            // Also notify admin
            $this->notifyAdminSystemUpdate([
                'type' => 'emergency_broadcast',
                'data' => $emergencyData
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to send emergency broadcast", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
