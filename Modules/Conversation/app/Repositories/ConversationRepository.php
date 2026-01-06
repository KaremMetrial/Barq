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
        $query = Conversation::with(['user', 'admin', 'store', 'couier']);

        if ($guard === 'vendor') {
           $vendorStores = \Modules\Store\Models\Store::where('id', auth('vendor')->user()->store_id)->pluck('id');
            $query->where(function ($q) use ($id, $vendorStores) {
                $q->where('vendor_id', $id)
                ->orWhereIn('store_id', $vendorStores);
            });

        } else {
            $query->where($column, $id);
        }

        return $query->where('order_id', request()->get('order_id', null))
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
