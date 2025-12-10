<?php

namespace Modules\Conversation\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Conversation\Services\ConversationService;
use Modules\Conversation\Http\Resources\ConversationResource;
use Modules\Conversation\Http\Requests\CreateConversationRequest;
use Modules\Conversation\Http\Requests\UpdateConversationRequest;
use App\Http\Resources\PaginationResource;

class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(private ConversationService $conversationService) {}

    private function getAuthenticatedGuard()
    {
        if (auth('user')->check()) return 'user';
        if (auth('vendor')->check()) return 'vendor';
        if (auth('sanctum')->check()) return 'admin';
        return null;
    }

    public function index(Request $request)
    {
        $guard = $this->getAuthenticatedGuard();
        $userId = auth($guard)->id();

        $perPage = $request->get('per_page', 15);
        $conversations = $this->conversationService->getConversationsByGuard($userId, $guard, $perPage);

        return $this->successResponse([
            'conversations' => ConversationResource::collection($conversations),
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

        // Check if user already has an active conversation (end_time is null)
        if ($guard !== 'admin') {
            $userId = auth($guard)->id();
            $existingConversation = $this->conversationService->getConversationsByGuard($userId, $guard, 1)->first();

            if ($existingConversation && is_null($existingConversation->end_time)) {
                // Return the existing active conversation
                return $this->successResponse([
                    'conversation' => new ConversationResource($existingConversation),
                ], __('message.success'));
            }
        }

        // Create new conversation if no active one exists
        $conversation = $this->conversationService->createConversation($data);

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }

    public function show($id)
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

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
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
