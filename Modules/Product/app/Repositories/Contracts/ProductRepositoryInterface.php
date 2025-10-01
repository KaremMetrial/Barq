<?php

namespace Modules\Product\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
interface ProductRepositoryInterface extends BaseRepositoryInterface
{
    public function home();
}
