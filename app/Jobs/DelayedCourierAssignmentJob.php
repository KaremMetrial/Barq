<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Order\Models\Order;
use Modules\Couier\Services\CacheBasedOrderAssignmentService;
use Illuminate\Support\Facades\Log;

class DelayedCourierAssignmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    /**
     * Create a new job instance.
     */
    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $order = Order::find($this->orderId);

            if (!$order) {
                Log::warning('Delayed courier assignment failed: Order not found', [
                    'order_id' => $this->orderId,
                ]);
                return;
            }

            // Check if order is still eligible for assignment
            if ($order->couier_id) {
                Log::info('Delayed courier assignment skipped: Order already has assigned courier', [
                    'order_id' => $order->id,
                ]);
                return;
            }

            if ($order->status->value != 'ready_for_delivery') {
                Log::info('Delayed courier assignment skipped: Order status changed', [
                    'order_id' => $order->id,
                    'current_status' => $order->status->value,
                ]);
                return;
            }

            // Check if order has required location data
            $hasPickupLocation = $order->store->address && $order->store->address->latitude;
            $hasDeliveryLocation = $order->deliveryAddress && $order->deliveryAddress->latitude;

            if (!$hasPickupLocation || !$hasDeliveryLocation) {
                Log::warning('Delayed courier assignment failed: Order missing required location data', [
                    'order_id' => $order->id,
                    'has_pickup' => $hasPickupLocation,
                    'has_delivery' => $hasDeliveryLocation,
                ]);
                return;
            }

            // Prepare order data for assignment
            $orderData = [
                'order_id' => $order->id,
                'pickup_lat' => $order->store->address->latitude,
                'pickup_lng' => $order->store->address->longitude,
                'delivery_lat' => $order->deliveryAddress->latitude,
                'delivery_lng' => $order->deliveryAddress->longitude,
                'priority_level' => 'normal',
            ];

            // Use cache-based assignment service
            $assignmentService = app(CacheBasedOrderAssignmentService::class);
            $assignment = $assignmentService->assignOrderToNearestCourier($orderData);

            if ($assignment) {
                Log::info('Delayed courier assignment successful for order: ' . $order->id);
                $this->handleSuccessfulAssignment($order, $assignment);
            } else {
                Log::info('Delayed courier assignment failed for order: ' . $order->id);
                $this->handleAssignmentFailure($order);
            }

        } catch (\Exception $e) {
            Log::error('Delayed courier assignment failed for order: ' . $this->orderId . ' - ' . $e->getMessage());
        }
    }

    /**
     * Handle successful courier assignment
     */
    private function handleSuccessfulAssignment($order, $assignment): void
    {
        // Update order with assigned courier
        $order->update(['couier_id' => $assignment->courier_id]);

        // Log successful assignment
        Log::info('Courier automatically assigned to order via delayed job', [
            'order_id' => $order->id,
            'courier_id' => $assignment->courier_id,
            'assignment_id' => $assignment->id,
            'estimated_distance' => $assignment->estimated_distance_km,
        ]);
    }

    /**
     * Handle assignment failure (no suitable couriers found)
     */
    private function handleAssignmentFailure($order): void
    {
        Log::warning('Delayed auto-assignment failed: No suitable couriers found', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'store_id' => $order->store_id,
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function retryUntil()
    {
        // Give assignment operation 5 minutes to complete
        return now()->addMinutes(5);
    }
}
