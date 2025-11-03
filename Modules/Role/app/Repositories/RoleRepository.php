<?php

namespace Modules\Role\Repositories;
use Modules\Role\Models\Role;
use Modules\Role\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\BaseRepository;
class RoleRepository extends BaseRepository implements RoleRepositoryInterface
{
    public function __construct(Role $model)
    {
        parent::__construct($model);
    }
}
