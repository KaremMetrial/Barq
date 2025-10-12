<?php

namespace Modules\Conversation\Repositories;

use Modules\Conversation\Models\Message;
use Modules\Conversation\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\BaseRepository;

class MessageRepository extends BaseRepository implements MessageRepositoryInterface
{
    public function __construct(Message $model)
    {
        parent::__construct($model);
    }

    public function findByConversation($conversationId)
    {
        return $this->model->where('conversation_id', $conversationId)->latest()->get();
    }
}
