<?php

namespace Modules\Couier\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Order\Models\Order;
use Modules\Couier\Services\CacheBasedOrderAssignmentService;
use Illuminate\Support\Facades\Log;

class AssignCourierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected int $orderId) {}

    /**
     * Execute the job.
     */
    public function handle(CacheBasedOrderAssignmentService $assignmentService): void
    {
        $order = Order::find($this->orderId);

        if (!$order) {
            return;
        }

        // 1. Check if order is still valid for assignment
        // Must be processing or ready_for_delivery, and NOT have a courier yet.
        if ($order->couier_id) {
            Log::info("AssignCourierJob: Order {$this->orderId} already has a courier.");
            return;
        }

        if (!in_array($order->status->value, ['processing', 'ready_for_delivery'])) {
            Log::info("AssignCourierJob: Order {$this->orderId} status ({$order->status->value}) is not eligible.");
            return;
        }

        Log::info("AssignCourierJob: Attempting to assign courier for Order {$this->orderId}");

        try {
            $orderData = [
                'order_id' => $order->id,
                'pickup_lat' => $order->store->address->latitude,
                'pickup_lng' => $order->store->address->longitude,
                'delivery_lat' => $order->deliveryAddress->latitude,
                'delivery_lng' => $order->deliveryAddress->longitude,
                'priority_level' => 'normal',
            ];

            $assignment = $assignmentService->assignOrderToNearestCourier($orderData);

            if ($assignment) {
                // Update courier_id
                $order->update(['couier_id' => $assignment->courier_id]);
                Log::info("AssignCourierJob: Successfully assigned Courier {$assignment->courier_id} to Order {$this->orderId}");
            } else {
                Log::warning("AssignCourierJob: No courier found for Order {$this->orderId}");
                // Optional: Retry later? Or leave it for the Ready For Delivery event/manual assignment
            }
        } catch (\Exception $e) {
            Log::error("AssignCourierJob: Error assigning order {$this->orderId}: " . $e->getMessage());
        }
    }
}
