<?php

namespace Modules\Order\Repositories;

use Modules\Order\Models\Order;
use Modules\Order\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\BaseRepository;

class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }
    public function getLastOrder(): ?Order
    {
        return $this->model->orderBy('created_at', 'desc')->first();
    }
    public function getStats(): array
    {
        // Get current user and apply appropriate filtering
        $user = auth()->user();
        $storeId = null;
        $userId = null;

        if ($user) {
            if ($user->tokenCan('vendor')) {
                $storeId = $user->store_id;
            } elseif ($user->tokenCan('user')) {
                $userId = $user->id;
            }
            // Admins see all stats (no filters)
        }

        return Order::getStats($storeId, $userId);
    }
}
