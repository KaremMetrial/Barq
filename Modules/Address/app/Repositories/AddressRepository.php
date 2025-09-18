<?php

namespace Modules\Address\Repositories;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use App\Repositories\BaseRepository;
class AddressRepository extends BaseRepository implements AddressRepositoryInterface
{
    public function __construct(Address $model)
    {
        parent::__construct($model);
    }
}
