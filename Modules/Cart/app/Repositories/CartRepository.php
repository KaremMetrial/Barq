<?php

namespace Modules\Cart\Repositories;
use Modules\Cart\Models\Cart;
use Modules\Cart\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\BaseRepository;
class CartRepository extends BaseRepository implements CartRepositoryInterface
{
    public function __construct(Cart $model)
    {
        parent::__construct($model);
    }
}
