<?php

namespace Modules\Conversation\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Conversation\Services\MessageService;
use Modules\Conversation\Http\Resources\MessageResource;
use Modules\Conversation\Http\Requests\CreateMessageRequest;
use Modules\Conversation\Http\Requests\UpdateMessageRequest;

class MessageController extends Controller
{
    use ApiResponse;

    public function __construct(private MessageService $messageService) {}

    private function getAuthenticatedGuard()
    {
        if (auth('user')->check()) return 'user';
        if (auth('vendor')->check()) return 'vendor';
        if (auth('sanctum')->check()) return 'admin';
        return null;
    }

    public function index($conversationId)
    {
        $messages = $this->messageService->getMessagesByConversation($conversationId);
        return $this->successResponse([
            'messages' => MessageResource::collection($messages),
        ], __('message.success'));
    }

    public function store(CreateMessageRequest $request)
    {
        $guard = $this->getAuthenticatedGuard();
        $data = $request->validated();
        $data['messageable_type'] = $guard;
        $data['messageable_id'] = auth($guard)->id();

        $message = $this->messageService->createMessage($data);

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
}
