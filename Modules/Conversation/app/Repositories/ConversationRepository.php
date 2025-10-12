<?php

namespace Modules\Conversation\Repositories;

use Modules\Conversation\Models\Conversation;
use Modules\Conversation\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\BaseRepository;

class ConversationRepository extends BaseRepository implements ConversationRepositoryInterface
{
    public function __construct(Conversation $model)
    {
        parent::__construct($model);
    }
    public function findByUser($userId)
    {
        return $this->model->where('user_id', $userId)->get();
    }
    public function findByGuard($id, $guard)
    {
        $column = $guard . '_id';
        return Conversation::where($column, $id)->get();
    }
}
