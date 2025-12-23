<?php

namespace Modules\Withdrawal\Repositories;
use Modules\Withdrawal\Models\Withdrawal;
use Modules\Withdrawal\Repositories\Contracts\WithdrawalRepositoryInterface;
use App\Repositories\BaseRepository;
class WithdrawalRepository extends BaseRepository implements WithdrawalRepositoryInterface
{
    public function __construct(Withdrawal $model)
    {
        parent::__construct($model);
    }
}
