<?php

namespace Modules\Couier\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $data;
    protected $courierId;
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
            new Channel('couriers'),
        ];
    }
    public function broadcastAs(): string
    {
        return 'new-order-assigned' . $this->courierId;
    }
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->data->messageId,
            'conversation_id' => $this->data->conversationId,
            'user_id' => $this->data->userId,
            'user_type' => $this->data->userType,
            'read_at' => now()->toDateTimeString(),
        ];
    }
}
