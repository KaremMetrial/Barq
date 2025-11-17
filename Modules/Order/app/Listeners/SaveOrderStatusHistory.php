<?php

namespace Modules\Order\Listeners;

use Modules\Order\Events\OrderStatusChanged;
use Modules\Order\Models\OrderStatusHistory;

class SaveOrderStatusHistory
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        OrderStatusHistory::create([
            'order_id' => $event->order->id,
            'status' => $event->newStatus->value,
            'changed_at' => now(),
            'note' => 'Status changed from ' . $event->oldStatus->value . ' to ' . $event->newStatus->value,
        ]);
    }
}
