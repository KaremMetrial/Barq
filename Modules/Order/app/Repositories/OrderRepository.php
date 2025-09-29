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
}
