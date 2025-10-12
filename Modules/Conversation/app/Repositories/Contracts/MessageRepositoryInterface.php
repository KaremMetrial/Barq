<?php

namespace Modules\Conversation\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;

interface MessageRepositoryInterface extends BaseRepositoryInterface
{
    public function findByConversation($conversationId);
}
