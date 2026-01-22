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
    public function findByGuard($id, $guard, $perPage = 15)
    {
        if ($guard == 'courier') {
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
            ->where('couier_id', request()->get('couier_id', null))
            ->where('end_time', null)
            ->paginate($perPage);
    }

    /**
     * Find conversations for admin/support (all conversations)
     */
    public function findAllForAdmin($perPage = 15, $filters = [])
    {
        return Conversation::with(['user', 'vendor', 'admin', 'order', 'couier'])->filter($filters)->paginate($perPage);
    }

    public function checkExisting(array $criteria)
    {
        $query = Conversation::query();

        // 1. Must be active
        $query->whereNull('end_time');

        // 2. Filter by context (Order, Type)
        if (!empty($criteria['order_id'])) {
            $query->where('order_id', $criteria['order_id']);
        }

        if (!empty($criteria['type'])) {
            $query->where('type', $criteria['type']);
        }

        // 3. Filter by Participants
        if (!empty($criteria['user_id'])) {
            $query->where('user_id', $criteria['user_id']);
        }

        if (!empty($criteria['couier_id'])) {
            $query->where('couier_id', $criteria['couier_id']);
        }

        if (!empty($criteria['vendor_id'])) {
            $query->where('vendor_id', $criteria['vendor_id']);
        }

        if (!empty($criteria['store_id'])) {
            $query->where('store_id', $criteria['store_id']);
        }

        if (!empty($criteria['admin_id'])) {
            $query->where('admin_id', $criteria['admin_id']);
        }

        return $query->latest()->first();
    }
}
