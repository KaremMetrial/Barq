<?php

namespace Modules\Couier\Repositories;
use Modules\Couier\Models\Couier;
use Modules\Couier\Repositories\Contracts\CouierRepositoryInterface;
use App\Repositories\BaseRepository;
class CouierRepository extends BaseRepository implements CouierRepositoryInterface
{
    public function __construct(Couier $model)
    {
        parent::__construct($model);
    }
}
