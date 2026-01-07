<?php

namespace Modules\Conversation\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Pagination\Paginator;
use App\Http\Resources\PaginationResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Conversation\Events\ConversationStarted;
use Modules\Conversation\Services\ConversationService;
use Modules\Conversation\Http\Resources\MessageResource;
use Modules\Conversation\Http\Resources\ConversationResource;
use Modules\Conversation\Http\Requests\CreateConversationRequest;
use Modules\Conversation\Http\Requests\UpdateConversationRequest;
use Modules\Conversation\Models\Conversation;

class ConversationController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private ConversationService $conversationService) {}

    private function getAuthenticatedGuard()
    {
        if (auth('user')->check()) return 'user';
        if (auth('vendor')->check()) return 'vendor';
        if(auth('courier')->check()) return 'courier';
        if (auth('admin')->check()) return 'admin';
        return null;
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Conversation::class);
        $guard = $this->getAuthenticatedGuard();
        $userId = auth($guard)->id();
        $perPage = $request->get('per_page', 15);
        $conversations = $this->conversationService->getConversationsByGuard($userId, $guard, $perPage);

        // Get messages for each conversation if requested
        $conversationsWithMessages = $conversations->map(function ($conversation) use ($request) {
            $conversationData = new ConversationResource($conversation);

            // Only load messages if specifically requested via include_messages parameter
            // or if it's a paginated request (page parameter exists)
            if ($request->has('include_messages') || $request->has('page')) {
                $messagesQuery = $conversation->messages()->orderBy('created_at', 'asc');
                $totalMessages = $messagesQuery->count();

                // Get messages with pagination
                $messagesPerPage = $request->get('messages_per_page', 15);
                $currentPage = $request->get('page', 1);

                $messages = $messagesQuery->paginate($messagesPerPage, ['*'], 'page', $currentPage);

                $conversationData->additional([
                    'messages' => [
                        'data' => MessageResource::collection($messages),
                        'pagination' => new PaginationResource($messages),
                    ]
                ]);
            }

            return $conversationData;
        });

        return $this->successResponse([
            'conversations' => $conversationsWithMessages,
            'pagination' => new PaginationResource($conversations),
        ], __('message.success'));
    }

    public function store(CreateConversationRequest $request)
    {
        $guard = $this->getAuthenticatedGuard();
        $data = $request->validated();
        // For admin, they can create conversations for users/vendors
        if ($guard !== 'admin') {
            $data[$guard . '_id'] = auth($guard)->id();
        }
        if ($guard === 'courier' && isset($data['store_id'])) {
            // For courier-store chats, use store_id instead of vendor_id
            unset($data['vendor_id']); // Remove vendor_id if present
        }


        // Check if user already has an active conversation (end_time is null)
        if ($guard !== 'admin') {
            $userId = auth($guard)->id();
            $existingConversation = $this->conversationService->getConversationsByGuard($userId, $guard, 1)->first();

            if ($existingConversation && is_null($existingConversation->end_time)) {
            $messagesQuery = $existingConversation->messages()->orderBy('created_at', 'asc');

            // Get the total count of messages in the conversation
            $totalMessages = $messagesQuery->count();

            // Pagination parameters
            $perPage = $request->get('per_page', 15);

            // Check if user wants a specific page or default to last page
            if ($request->has('page')) {
                // User specified a page, use standard pagination
                $page = $request->get('page', 1);
                $messages = $messagesQuery->paginate($perPage, ['*'], 'page', $page);
            } else {
                // Default behavior: show last page (most recent messages)
                $lastPage = ceil($totalMessages / $perPage);
                $skip = max(0, $totalMessages - $perPage);
                $messages = $messagesQuery->skip($skip)->take($perPage)->get();

                // Create a manual paginator to maintain the response structure
                $messages = new LengthAwarePaginator(
                    $messages,
                    $totalMessages,
                    $perPage,
                    $lastPage,
                    ['path' => Paginator::resolveCurrentPath()]
                );
            }

            return $this->successResponse([
                    'conversation' => new ConversationResource($existingConversation),
                    'messages' => null
                ], __('message.success'));
            }
        }

        // Create new conversation if no active one exists
        $conversation = $this->conversationService->createConversation($data);

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
            'messages' => null
        ], __('message.success'));
    }

    public function show($id, Request $request)
    {
        $guard = $this->getAuthenticatedGuard();

        $conversation = $this->conversationService->getConversationById($id);

        // For admin, they can view any conversation
        if ($guard !== 'admin') {
            // For users/vendors, they can only view their own conversations
            if (!$conversation || $conversation->{$guard . '_id'} !== auth($guard)->id()) {
                return $this->errorResponse(__('message.unauthorized'), 403);
            }
        }

        $messagesQuery = $conversation->messages()->orderBy('created_at', 'asc');
        $totalMessages = $messagesQuery->count();

        // Pagination parameters
        $perPage = $request->get('per_page', 15);

        // Check if user wants a specific page or default to last page
        if ($request->has('page')) {
            // User specified a page, use standard pagination
            $page = $request->get('page', 1);
            $messages = $messagesQuery->paginate($perPage, ['*'], 'page', $page);
        } else {
            // Default behavior: show last page (most recent messages)
            $lastPage = ceil($totalMessages / $perPage);
            $skip = max(0, $totalMessages - $perPage);
            $messages = $messagesQuery->skip($skip)->take($perPage)->get();

            // Create a manual paginator to maintain the response structure
            $messages = new LengthAwarePaginator(
                $messages,
                $totalMessages,
                $perPage,
                $lastPage,
                ['path' => Paginator::resolveCurrentPath()]
            );
        }
        $this->markMessagesAsRead($conversation, $guard);

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
            'messages' => [
                'data' => MessageResource::collection($messages),
                'pagination' => new PaginationResource($messages),
            ],
        ], __('message.success'));
    }

    public function update(UpdateConversationRequest $request, $id)
    {
        $guard = $this->getAuthenticatedGuard();

        // For admin, they can update any conversation
        if ($guard === 'admin') {
            $conversation = $this->conversationService->updateConversation($id, $request->validated());
        } else {
            // For users/vendors, they can only update their own conversations
            $conversation = $this->conversationService->getConversationById($id);
            if (!$conversation || $conversation->{$guard . '_id'} !== auth($guard)->id()) {
                return $this->errorResponse(__('message.unauthorized'), 403);
            }
            $conversation = $this->conversationService->updateConversation($id, $request->validated());
        }

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }

    private function markMessagesAsRead($conversation, $guard)
    {
        $currentUserId = auth($guard)->id();
        $currentUserType = $guard;

        $messagesToMark = $conversation->messages()
            ->where(function ($query) use ($currentUserId, $currentUserType) {
                $query->where(function ($q) use ($currentUserId, $currentUserType) {
                    $q->where('messageable_id', '!=', $currentUserId)
                        ->orWhere('messageable_type', '!=', $currentUserType);
                })->where('is_read', false);
            })
            ->get();

        foreach ($messagesToMark as $message) {
            $readBy = $message->read_by ?? [];
            if (!in_array($currentUserId, $readBy)) {
                $readBy[] = $currentUserId;

                $message->update([
                    'is_read' => true,
                ]);
            }
        }
    }
    public function destroy($id)
    {
        $guard = $this->getAuthenticatedGuard();

        // For admin, they can delete any conversation
        if ($guard === 'admin') {
            $this->conversationService->deleteConversation($id);
        } else {
            // For users/vendors, they can only delete their own conversations
            $conversation = $this->conversationService->getConversationById($id);
            if (!$conversation || $conversation->{$guard . '_id'} !== auth($guard)->id()) {
                return $this->errorResponse(__('message.unauthorized'), 403);
            }
            $this->conversationService->deleteConversation($id);
        }

        return $this->successResponse(null, __('message.success'));
    }

    public function endConversation($id)
    {
        $guard = $this->getAuthenticatedGuard();

        // Only admin can end conversations
        if ($guard !== 'admin') {
            return $this->errorResponse(__('message.unauthorized'), 403);
        }

        $conversation = $this->conversationService->endConversation($id);
        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }
}
