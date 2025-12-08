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
    public function findByGuard($id, $guard,$perPage = 15)
    {
        $column = $guard . '_id';
        return Conversation::with(['user', 'admin'])
            ->where($column, $id)
            ->where('end_time', null)
            ->paginate($perPage);
    }

    /**
     * Find conversations for admin/support (all conversations)
     */
    public function findAllForAdmin($perPage = 15)
    {
        return Conversation::with(['user', 'vendor', 'admin', 'order'])->paginate($perPage);
    }
}
