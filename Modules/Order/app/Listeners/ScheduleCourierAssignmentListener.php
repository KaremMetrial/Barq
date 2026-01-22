<?php

namespace Modules\Order\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Order\Events\OrderStatusChanged;
use Modules\Couier\Jobs\AssignCourierJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ScheduleCourierAssignmentListener
{
    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        // Only run when status changes to PROCESSING
        if ($event->newStatus->value !== 'processing') {
            return;
        }

        $order = $event->order;

        // Calculate max preparation time from order items
        $maxPrepTimeMinutes = 0;

        foreach ($order->items as $item) {
            $product = $item->product;
            if ($product && $product->preparation_time) {
                $prepTime = $product->preparation_time;
                $unit = strtolower($product->preparation_time_unit ?? 'minutes'); // Default to minutes

                // Convert to minutes
                if (str_contains($unit, 'hour')) {
                    $prepTime *= 60;
                }

                if ($prepTime > $maxPrepTimeMinutes) {
                    $maxPrepTimeMinutes = $prepTime;
                }
            }
        }

        // Default prep time if none set (e.g. 15 mins)
        if ($maxPrepTimeMinutes === 0) {
            $maxPrepTimeMinutes = 15;
        }

        // Calculate dispatch time (e.g., 5 mins before finish)
        // Buffer time: We want courier to arrive slightly before or exactly when ready.
        // Let's assume we want to trigger assignment 10 minutes before ready time 
        // to give time for courier to accept and travel.
        // If prep time is small (e.g. < 15 mins), assign immediately.

        $bufferMinutes = 10;

        // If prep time is short, just assign immediately (or very short delay)
        if ($maxPrepTimeMinutes <= $bufferMinutes) {
            AssignCourierJob::dispatch($order->id);
            Log::info("ScheduleCourierAssignmentListener: Dispatched immediate assignment for Order {$order->id} (Prep time: {$maxPrepTimeMinutes}m)");
            return;
        }

        $delayMinutes = $maxPrepTimeMinutes - $bufferMinutes;
        $delay = now()->addMinutes($delayMinutes);

        AssignCourierJob::dispatch($order->id)->delay($delay);

        Log::info("ScheduleCourierAssignmentListener: Scheduled assignment for Order {$order->id} in {$delayMinutes} minutes (Prep time: {$maxPrepTimeMinutes}m)");
    }
}
