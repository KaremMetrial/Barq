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
    protected $secondsLeft;
    /**
     * Create a new event instance.
     */
    public function __construct($courierId, $orderId, $secondsLeft)
    {
        $this->courierId = $courierId;
        $this->orderId = $orderId;
        $this->secondsLeft = $secondsLeft;
    }
    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('couriers'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'order-expiring.' . $this->courierId;
    }
    public function broadcastWith(): array
    {
        return [
                'order_id' => $this->orderId,
                'seconds_left' => $this->secondsLeft,
                'expires_at' => now()->addSeconds($this->secondsLeft)->toISOString(),
        ];
    }
}
