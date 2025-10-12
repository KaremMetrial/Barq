<?php

namespace Modules\Conversation\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Conversation\Services\ConversationService;
use Modules\Conversation\Http\Resources\ConversationResource;
use Modules\Conversation\Http\Requests\CreateConversationRequest;
use Modules\Conversation\Http\Requests\UpdateConversationRequest;

class ConversationController extends Controller
{
    use ApiResponse;

    public function __construct(private ConversationService $conversationService) {}

    private function getAuthenticatedGuard()
    {
        return auth('user')->check() ? 'user' : 'vendor';
    }

    public function index()
    {
        $guard = $this->getAuthenticatedGuard();
        $userId = auth($guard)->id();

        $conversations = $this->conversationService->getConversationsByGuard($userId, $guard);

        return $this->successResponse([
            'conversations' => ConversationResource::collection($conversations),
        ], __('message.success'));
    }

    public function store(CreateConversationRequest $request)
    {
        $guard = $this->getAuthenticatedGuard();
        $data = $request->validated();
        $data[$guard . '_id'] = auth($guard)->id();

        $conversation = $this->conversationService->createConversation($data);

        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }

    public function show($id)
    {
        $conversation = $this->conversationService->getConversationById($id);
        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }

    public function update(UpdateConversationRequest $request, $id)
    {
        $conversation = $this->conversationService->updateConversation($id, $request->validated());
        return $this->successResponse([
            'conversation' => new ConversationResource($conversation),
        ], __('message.success'));
    }

    public function destroy($id)
    {
        $this->conversationService->deleteConversation($id);
        return $this->successResponse(null, __('message.success'));
    }
}
