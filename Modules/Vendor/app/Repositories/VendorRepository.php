<?php

namespace Modules\Vendor\Repositories;
use Modules\Vendor\Models\Vendor;
use Modules\Vendor\Repositories\Contracts\VendorRepositoryInterface;
use App\Repositories\BaseRepository;
class VendorRepository extends BaseRepository implements VendorRepositoryInterface
{
    public function __construct(Vendor $model)
    {
        parent::__construct($model);
    }
}
