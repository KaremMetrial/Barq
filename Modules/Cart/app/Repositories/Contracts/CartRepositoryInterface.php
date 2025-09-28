<?php

namespace Modules\Cart\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
interface CartRepositoryInterface extends BaseRepositoryInterface
{
    public function findByField(string $field, mixed $value, array $relations = []): \Illuminate\Support\Collection;
}
