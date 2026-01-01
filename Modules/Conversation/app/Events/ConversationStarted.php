<?php

namespace Modules\Conversation\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Modules\Conversation\Models\Conversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ConversationStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Conversation $conversation) {}

    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('order.' . $this->conversation->order_id),
        ];
    }
    public function broadcastAs(): string
    {
        return 'conversation.started.id.' . $this->conversation->id;
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'type' => $this->conversation->type->value,
            'order_id' => $this->conversation->order_id,
            'participants' => $this->getParticipantsData(),
            'started_at' => $this->conversation->start_time,
            'messages' => $this->conversation->messages()->orderBy('created_at')->get()->map(function ($message) {
                return [
                    'id' => $message->id,
                    'messageable_type' => $message->messageable_type,
                    'messageable_id' => $message->messageable_id,
                    'content' => $message->content,
                    'sent_at' => $message->created_at->format('Y-m-d H:i:s'),
                ];
            })->toArray(),
        ];
    }
    private function getParticipantsData(): array
    {
        $participants = [];

        if ($this->conversation->user) {
            $participants[] = [
                'type' => 'user',
                'id' => $this->conversation->user->id,
                'name' => $this->conversation->user->name,
            ];
        }

        if ($this->conversation->couier) {
            $participants[] = [
                'type' => 'courier',
                'id' => $this->conversation->couier->id,
                'name' => $this->conversation->couier->first_name . ' ' . $this->conversation->couier->last_name,
            ];
        }

        if ($this->conversation->admin) {
            $participants[] = [
                'type' => 'admin',
                'id' => $this->conversation->admin->id,
                'name' => $this->conversation->admin->name,
            ];
        }

        if ($this->conversation->vendor) {
            $participants[] = [
                'type' => 'store',
                'id' => $this->conversation->vendor->store->id,
                'name' => $this->conversation->vendor->store->name,
            ];
        }

        return $participants;
    }

}
