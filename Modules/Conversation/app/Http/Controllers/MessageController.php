<?php

namespace Modules\Conversation\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Modules\Conversation\Services\MessageService;
use App\Services\PusherService;
use Modules\Conversation\Http\Resources\MessageResource;
use Modules\Conversation\Http\Requests\CreateMessageRequest;
use Modules\Conversation\Http\Requests\UpdateMessageRequest;
use Modules\Order\Events\OrderAssignmentToCourier;

class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private MessageService $messageService,
        private PusherService $pusherService
    ) {}

    private function getAuthenticatedGuard()
    {
        if (auth('user')->check()) return 'user';
        if (auth('vendor')->check()) return 'vendor';
        if (auth('admin')->check()) return 'admin';
        if(auth('courier')->check()) return 'courier';
        return null;
    }

    public function index($conversationId)
    {
        $messages = $this->messageService->getMessagesByConversation($conversationId);
        return $this->successResponse([
            'messages' => MessageResource::collection(resource: $messages),
        ], __('message.success'));
    }

    public function store(CreateMessageRequest $request)
    {
        $guard = $this->getAuthenticatedGuard();
        if (!$guard) {
            return $this->errorResponse('Unauthorized', 401);
        }

        $data = $request->validated();
        $data['messageable_type'] = $guard;
        $data['messageable_id'] = auth($guard)->id();
        $message = $this->messageService->createMessage($data);

        if ($guard === 'admin' && $message->conversation->admin_id === null) {
            $message->conversation->update(['admin_id' => auth($guard)->id()]);
        }

        // ✅ get socket id from Flutter (optional, to suppress echo)
        $socketId = $request->input('socket_id');

        // ✅ broadcast via PusherService (socket-aware)
        $this->pusherService->broadcastMessage($message, $socketId);

        // ✅ Trigger OrderAssignmentToCourier event if courier sends message for an order
        if ($guard === 'courier' && $message->conversation->order_id) {
            $order = $message->conversation->order;
            $courier = auth('courier')->user();

            if ($order && $courier) {
                OrderAssignmentToCourier::dispatch($order, $courier);
            }
        }

        return $this->successResponse([
            'message' => new MessageResource($message),
        ], __('message.success'));
    }

    public function show($id)
    {
        $message = $this->messageService->getMessageById($id);
        return $this->successResponse(['message' => new MessageResource($message)], __('message.success'));
    }

    public function update(UpdateMessageRequest $request, $id)
    {
        $message = $this->messageService->updateMessage($id, $request->validated());
        return $this->successResponse(['message' => new MessageResource($message)], __('message.success'));
    }

    public function destroy($id)
    {
        $this->messageService->deleteMessage($id);
        return $this->successResponse(null, __('message.success'));
    }

    /**
     * Mark a message as read
     */
    public function markAsRead($messageId)
    {
        $guard = $this->getAuthenticatedGuard();
        if (!$guard) {
            return $this->errorResponse('Unauthorized', 401);
        }

        try {
            $message = $this->messageService->getMessageById($messageId);
            if (!$message) {
                return $this->errorResponse('Message not found', 404);
            }

            // Use the Pusher service to mark as read and broadcast
            $this->pusherService->markMessageAsRead($message, auth($guard)->id(), $guard);

            return $this->successResponse([
                'message' => 'Message marked as read',
                'read_at' => $message->read_at,
                'read_by' => $message->read_by,
            ], __('message.success'));
        } catch (\Exception $e) {
            Log::error('Mark as read failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to mark message as read', 500);
        }
    }

    /**
     * Send typing indicator
     */
    public function typingIndicator(Request $request, $conversationId)
    {
        $guard = $this->getAuthenticatedGuard();
        if (!$guard) {
            return $this->errorResponse('Unauthorized', 401);
        }

        try {
            $isTyping = $request->boolean('is_typing', true);

            // Use the Pusher service to broadcast typing indicator
            $this->pusherService->broadcastTypingIndicator(
                $conversationId,
                auth($guard)->id(),
                $guard,
                $isTyping
            );

            return $this->successResponse([
                'status' => $isTyping ? 'typing' : 'stopped',
                'conversation_id' => $conversationId,
            ], __('message.success'));
        } catch (\Exception $e) {
            Log::error('Typing indicator failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to send typing indicator', 500);
        }
    }

    /**
     * Get Pusher authentication for private channels
     */
    public function pusherAuth(Request $request)
    {
        $guard = $this->getAuthenticatedGuard();
        if (!$guard) {
            return $this->errorResponse('Unauthorized', 401);
        }

        try {
            $channelName = $request->input('channel_name');
            $socketId = $request->input('socket_id');

            if (!$channelName || !$socketId) {
                return $this->errorResponse('Channel name and socket ID are required', 400);
            }

            $auth = $this->pusherService->getPusherAuth(
                $channelName,
                $socketId,
                auth($guard)->id(),
                $guard
            );

            return response()->json($auth);
        } catch (\Exception $e) {
            Log::error('Pusher auth failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to authenticate with Pusher', 500);
        }
    }
}
