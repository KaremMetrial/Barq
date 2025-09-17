<?php

namespace Modules\Unit\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
interface UnitRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllCodes(): array;
}
