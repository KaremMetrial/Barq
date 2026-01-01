<?php

namespace Modules\Conversation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Modules\Conversation\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Modules\Conversation\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ConversationMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Message $message) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->message->conversation->order_id),
        ];
    }
    public function broadcastAs(): string
    {
        return 'conversation.message.sent.id';

    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->messageable_id,
            'sender_type' => $this->message->messageable_type,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
