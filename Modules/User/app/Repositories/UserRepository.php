<?php

namespace Modules\User\Repositories;
use Modules\User\Models\User;
use Modules\User\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\BaseRepository;
class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
