<?php

namespace Modules\Order\Events;

use Modules\Order\Models\Order;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderNotAcceptedOnTime implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $order;
    public $timeoutReason;

    /**
     * Create a new event instance.
     */
    public function __construct(Order $order, string $timeoutReason = 'Restaurant did not accept order within 5 minutes')
    {
        $this->order = $order;
        $this->timeoutReason = $timeoutReason;
    }

    public function broadcastOn(): array
    {
        return [
            new \Illuminate\Broadcasting\PrivateChannel('order.' . $this->order->id),
            new \Illuminate\Broadcasting\PrivateChannel('admin.orders'),
            new \Illuminate\Broadcasting\PrivateChannel('store.' . $this->order->store_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.timeout.expired';
    }

    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'reason' => $this->timeoutReason,
            'cancelled_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
