<?php

namespace Modules\Couier\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAssignedExpired implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    protected $courierId;
    protected $orderId;
    protected $reason;
    protected $expiredAt;
    /**
     * Create a new event instance.
     */
    public function __construct($courierId, $orderId, $reason, $expiredAt)
    {
        $this->courierId = $courierId;
        $this->orderId = $orderId;
        $this->reason = $reason;
        $this->expiredAt = $expiredAt;
    }
    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('couriers.'.$this->courierId),
        ];
    }
    public function broadcastAs(): string
    {
        return 'order-expired';
    }
    public function broadcastWith(): array
    {
        return [
                'order_id' => $this->orderId,
                'reason' => $this->reason,
                'expires_at' => $this->expiredAt,
        ];
    }
}
