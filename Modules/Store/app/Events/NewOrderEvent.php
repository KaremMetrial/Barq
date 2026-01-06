<?php

namespace Modules\Store\Events;

use Modules\Order\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $order;
    /**
     * Create a new event instance.
     */
    public function __construct(Order $order) {
        $this->order = $order;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('store.' . $this->order->store_id . '.new-orders'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'new-order';
    }
    public function broadcastWith(): array
    {
        return [
            'order' => new \Modules\Order\Http\Resources\OrderResource($this->order->load(['deliveryAddress', 'items.product','user','paymentMethod']))
        ];
    }
}
