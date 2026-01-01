<?php

namespace Modules\Order\Jobs;

use Modules\Order\Models\Order;
use App\Enums\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Order\Models\OrderStatusHistory;
use Modules\Order\Services\OrderNotificationService;

class CancelOrderJob implements ShouldQueue
{
    use Dispatchable, Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function handle()
    {

        $this->order->refresh();
        
        if ($this->order->status == OrderStatus::PENDING) {
            $this->order->update([
                'status' => OrderStatus::CANCELLED,
            ]);

            OrderStatusHistory::create([
                'order_id' => $this->order->id,
                'status' => OrderStatus::CANCELLED,
                'changed_at' => now(),
                'changed_by' => 'system:timeout',
                'note' => 'Order auto-cancelled due to timeout'
            ]);

            // Send notifications (e.g., to the user and store)
            $this->sendNotifications($this->order);
        }
    }

    private function sendNotifications(Order $order)
    {
        // Add your notification logic here
    }
}
