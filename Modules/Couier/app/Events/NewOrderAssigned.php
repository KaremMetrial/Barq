<?php

namespace Modules\Couier\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Couier\Http\Resources\FullOrderResource;

class NewOrderAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $data;
    public $courierId;
    /**
     * Create a new event instance.
     */
    public function __construct($data, $courierId)
    {
        $this->data = $data;
        $this->courierId = $courierId;
    }

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('couriers.' . $this->courierId),
        ];
    }
    public function broadcastAs(): string
    {
        return 'new-order-assigned';
    }
    public function broadcastWith(): array
    {
        return [
            'assignment' =>    new FullOrderResource($this->data)
        ];
    }
}
