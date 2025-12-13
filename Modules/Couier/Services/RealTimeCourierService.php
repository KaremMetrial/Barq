<?php

namespace Modules\Couier\Services;

use Pusher\Pusher;
use Modules\Couier\Models\CourierOrderAssignment;
use Modules\Couier\Events\OrderAssignedToCourier;
use Modules\Couier\Events\OrderAcceptedByCourier;
use Modules\Couier\Events\OrderStatusChanged;
use Illuminate\Support\Facades\Log;

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
    public function notifyOrderAssigned(int $courierId, CourierOrderAssignment $assignment): void
    {
        try {
            $data = [
                'order_id' => $assignment->order_id,
                'assignment_id' => $assignment->id,
                'expires_in' => $assignment->time_remaining ?? 120,
                'pickup_coordinates' => $assignment->pickup_coordinates,
                'delivery_coordinates' => $assignment->delivery_coordinates,
                'estimated_distance' => $assignment->estimated_distance_km,
                'estimated_earning' => $assignment->estimated_earning,
                'priority_level' => $assignment->priority_level,
                'assigned_at' => $assignment->assigned_at->toISOString(),
            ];

            $this->pusher->trigger("couriers", "new-order-assigned.{$courierId}", $data);

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
            $this->pusher->trigger("couriers", "order-expiring.{$courierId}", [
                'order_id' => $orderId,
                'seconds_left' => $secondsLeft,
                'expires_at' => now()->addSeconds($secondsLeft)->toISOString(),
            ]);

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
            $this->pusher->trigger("couriers", "order-expired.{$courierId}", [
                'order_id' => $orderId,
                'reason' => $reason,
                'expired_at' => now()->toISOString(),
            ]);

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
     * Notify courier location updates for real-time tracking
     */
    public function updateCourierLocation(int $courierId, float $lat, float $lng): void
    {
        try {
            $this->pusher->trigger("couriers", "location-update.{$courierId}", [
                'lat' => $lat,
                'lng' => $lng,
                'timestamp' => now()->toISOString(),
                'accuracy' => null, // Can be added if GPS accuracy is available
            ]);

            Log::info("Pusher: Courier location updated", [
                'courier_id' => $courierId,
                'lat' => $lat,
                'lng' => $lng
            ]);

        } catch (\Exception $e) {
            Log::error("Pusher: Failed to update courier location", [
                'courier_id' => $courierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Notify order status changes
     */
    public function notifyOrderStatusChanged(int $orderId, string $newStatus, array $additionalData = []): void
    {
        try {
            $data = array_merge([
                'order_id' => $orderId,
                'status' => $newStatus,
                'changed_at' => now()->toISOString(),
            ], $additionalData);

            $this->pusher->trigger("orders", "order-status-changed.{$orderId}", $data);

            Log::info("Pusher: Order status changed", [
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
