<?php

namespace Modules\Conversation\Repositories;

use Modules\Conversation\Models\Conversation;
use Modules\Conversation\Repositories\Contracts\ConversationRepositoryInterface;
use App\Repositories\BaseRepository;

use function PHPSTORM_META\type;

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
        if($guard == 'courier'){
            $guard = 'couier';
        }
        $column = $guard . '_id';
        return Conversation::with(['user', 'admin'])
            ->where($column, $id)
            ->where('type', request()->get('type', 'support'))
            ->where('end_time', null)
            ->paginate($perPage);
    }

    /**
     * Find conversations for admin/support (all conversations)
     */
    public function findAllForAdmin($perPage = 15, $filters = [])
    {
        return Conversation::with(['user', 'vendor', 'admin', 'order' ,'couier'])->filter($filters)->paginate($perPage);
    }
}
