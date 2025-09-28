<?php

namespace Modules\Admin\Repositories;
use Modules\Admin\Models\Admin;
use Modules\Admin\Repositories\Contracts\AdminRepositoryInterface;
use App\Repositories\BaseRepository;
class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    public function __construct(Admin $model)
    {
        parent::__construct($model);
    }
}
