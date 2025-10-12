<?php

namespace Modules\Conversation\Services;

use Illuminate\Support\Facades\DB;
use Modules\Conversation\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Modules\Conversation\Repositories\MessageRepository;

class MessageService
{
    public function __construct(
        protected MessageRepository $messageRepository
    ) {}

    public function getMessagesByConversation($conversationId): Collection
    {
        return $this->messageRepository->findByConversation($conversationId);
    }

    public function createMessage(array $data): ?Message
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($v) => !blank($v));
            return $this->messageRepository->create($data);
        });
    }

    public function getMessageById(int $id): ?Message
    {
        return $this->messageRepository->find($id);
    }

    public function updateMessage(int $id, array $data): ?Message
    {
        return DB::transaction(function () use ($id, $data) {
            $data = array_filter($data, fn($v) => !blank($v));
            return $this->messageRepository->update($id, $data);
        });
    }

    public function deleteMessage(int $id): bool
    {
        return $this->messageRepository->delete($id);
    }
}
