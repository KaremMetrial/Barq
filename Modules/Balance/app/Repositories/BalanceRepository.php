<?php

namespace Modules\Balance\Repositories;
use Modules\Balance\Models\Balance;
use Modules\Balance\Repositories\Contracts\BalanceRepositoryInterface;
use App\Repositories\BaseRepository;
class BalanceRepository extends BaseRepository implements BalanceRepositoryInterface
{
    public function __construct(Balance $model)
    {
        parent::__construct($model);
    }
}
