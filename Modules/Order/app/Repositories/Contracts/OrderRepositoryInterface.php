<?php

namespace Modules\Order\Repositories\Contracts;

use Modules\Order\Models\Order;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface OrderRepositoryInterface extends BaseRepositoryInterface
{
    public function getLastOrder(): ?Order;
}
