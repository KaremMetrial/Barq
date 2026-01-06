<?php

namespace Modules\Order\Events;

use Modules\Order\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Modules\Couier\Models\Couier;
use Modules\Couier\Services\CourierLocationCacheService;

class OrderAssignmentToCourier implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;
    public $courier;
    public $unreadMessagesCount;
    public $lat;
    public $lng;

    /**
     * Create a new event instance.
     *
     * @param  \Modules\Order\Models\Order  $order
     * @param  \Modules\Couier\Models\Couier  $courier
     */
    public function __construct(Order $order, Couier $courier, int $unreadMessagesCount, $lat = null,  $lng = null)
    {
        $this->order = $order;
        $this->courier = $courier;
        $this->unreadMessagesCount = $unreadMessagesCount;
        $this->lat = $lat;
        $this->lng = $lng;
    }

    /**
     * Get the channels the event should be broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->order->id),
        ];
    }
    public function broadcastAs(): string
    {
            return 'order.assigned_to_courier';
    }
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'courier' => [
                'id' => $this->courier->id,
                'name' => $this->courier->first_name . ' ' . $this->courier->last_name,
                'phone' => $this->courier->phone,
                'avatar' => $this->courier->avatar ? asset('storage/' . $this->courier->avatar) : null,
                'assigned_at' => now()->format('Y-m-d H:i:s'),
                'unread_messages_count' => $this->unreadMessagesCount,
                'lat' => $this->lat ? (string) $this->lat : null,
                'lng' => $this->lng ? (string) $this->lng : null,
            ],
        ];
    }
}
