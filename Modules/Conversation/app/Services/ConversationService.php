<?php

namespace Modules\Conversation\Services;

use Illuminate\Support\Facades\DB;
use Modules\Conversation\Models\Conversation;
use Illuminate\Database\Eloquent\Collection;
use Modules\Conversation\Repositories\ConversationRepository;

class ConversationService
{
    public function __construct(
        protected ConversationRepository $ConversationRepository
    ) {}

    /**
     * Get all conversations for a specific user.
     */
    public function getConversationsByUser($userId): Collection
    {
        return $this->ConversationRepository->findByUser($userId);
    }

    /**
     * Create a new conversation for the user.
     */
    public function createConversation(array $data): ?Conversation
    {
        return DB::transaction(function () use ($data) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ConversationRepository->create($data);
        });
    }

    /**
     * Get a specific conversation by ID.
     */
    public function getConversationById(int $id): ?Conversation
    {
        return $this->ConversationRepository->find($id);
    }

    /**
     * Update a conversation.
     */
    public function updateConversation(int $id, array $data): ?Conversation
    {
        return DB::transaction(function () use ($data, $id) {
            $data = array_filter($data, fn($value) => !blank($value));
            return $this->ConversationRepository->update($id, $data);
        });
    }

    /**
     * Delete a conversation.
     */
    public function deleteConversation(int $id): bool
    {
        return $this->ConversationRepository->delete($id);
    }
    public function getConversationsByGuard($id, $guard, $perPage = 15)
    {
        if ($guard === 'admin') {
            return $this->ConversationRepository->findAllForAdmin($perPage, request()->all());
        }
        return $this->ConversationRepository->findByGuard($id, $guard, $perPage);
    }

    /**
     * End a conversation (set end_time).
     */
    public function endConversation(int $id): ?Conversation
    {
        return DB::transaction(function () use ($id) {
            $conversation = $this->ConversationRepository->find($id);
            if (!$conversation) {
                return null;
            }

            return $this->ConversationRepository->update($id, [
                'end_time' => now(),
            ]);
        });
    }

    public function findExisting(array $criteria)
    {
        return $this->ConversationRepository->checkExisting($criteria);
    }
}
