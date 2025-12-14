<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Events\MessageRead;
use Modules\Conversation\Models\Message;

class PusherService
{
    /**
     * Broadcast a new message to Pusher
     */
    public function broadcastMessage(Message $message, ?string $socketId = null): void
    {
        try {
            $event = new MessageSent($message);

            if ($socketId) {
                broadcast($event)->socket($socketId)->toOthers();
            } else {
                broadcast($event)->toOthers();
            }
        } catch (\Exception $e) {
            Log::error('Pusher message broadcast failed: ' . $e->getMessage());
            // Optionally send fallback notification or queue for retry
        }
    }

    /**
     * Broadcast typing indicator
     */
    public function broadcastTypingIndicator(
        int $conversationId,
        int $userId,
        string $userType,
        bool $isTyping
    ): void {
        try {
            event(new TypingIndicator($conversationId, $userId, $userType, $isTyping));
        } catch (\Exception $e) {
            Log::error('Pusher typing indicator failed: ' . $e->getMessage());
        }
    }

    /**
     * Broadcast message read receipt
     */
    public function broadcastMessageRead(
        int $messageId,
        int $conversationId,
        int $userId,
        string $userType
    ): void {
        try {
            event(new MessageRead($messageId, $conversationId, $userId, $userType));
        } catch (\Exception $e) {
            Log::error('Pusher read receipt failed: ' . $e->getMessage());
        }
    }

    /**
     * Mark message as read and broadcast the event
     */
    public function markMessageAsRead(Message $message, int $userId, string $userType): void
    {
        try {
            // Update read status
            $readBy = $message->read_by ?? [];
            if (!in_array($userId, $readBy)) {
                $readBy[] = $userId;
                $message->update([
                    'read_at' => now(),
                    'read_by' => $readBy
                ]);
            }

            // Broadcast the read receipt
            $this->broadcastMessageRead(
                $message->id,
                $message->conversation_id,
                $userId,
                $userType
            );
        } catch (\Exception $e) {
            Log::error('Mark message as read failed: ' . $e->getMessage());
        }
    }

    /**
     * Get Pusher authentication for private channels
     */
    public function getPusherAuth($channelName, $socketId, $userId = null, $userType = null): array
    {
        try {
            $pusher = new \Pusher\Pusher(
                config('broadcasting.connections.pusher.key'),
                config('broadcasting.connections.pusher.secret'),
                config('broadcasting.connections.pusher.app_id'),
                config('broadcasting.connections.pusher.options')
            );

            $auth = $pusher->socket_auth($channelName, $socketId, json_encode([
                'user_id' => $userId,
                'user_type' => $userType,
                'time' => time()
            ]));

            return json_decode($auth, true);
        } catch (\Exception $e) {
            Log::error('Pusher auth failed: ' . $e->getMessage());
            return [
                'error' => 'Failed to authenticate with Pusher',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Check if Pusher is configured and available
     */
    public function isPusherAvailable(): bool
    {
        return config('broadcasting.default') === 'pusher' &&
               !empty(config('broadcasting.connections.pusher.key')) &&
               !empty(config('broadcasting.connections.pusher.secret')) &&
               !empty(config('broadcasting.connections.pusher.app_id'));
    }
}
