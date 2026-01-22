<?php

namespace Modules\Couier\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Couier\Events\NewOrderAssigned;
use Modules\Order\Services\OrderNotificationService;
use Modules\Couier\Models\Couier;
use Modules\Order\Models\Order;
use Illuminate\Support\Facades\Log;

class SendCourierOrderNotification implements ShouldQueue
{
    public function __construct(protected OrderNotificationService $notificationService) {}

    public function handle(NewOrderAssigned $event): void
    {
        try {
            /** @var \Modules\Couier\Models\CourierOrderAssignment $assignment */
            $assignment = $event->data;
            if (!$assignment || !$assignment->courier || !$assignment->order) {
                return;
            }

            $this->notificationService->sendOrderAssignedNotificationToCourier(
                $assignment->courier,
                $assignment->order
            );
        } catch (\Exception $e) {
            Log::error("Failed to send courier assignment push notification", [
                'error' => $e->getMessage()
            ]);
        }
    }
}
