<?php

namespace Modules\Otp\Repositories;

use Modules\Otp\Models\Otp;
use Modules\Otp\Repositories\Contracts\OtpRepositoryInterface;
use App\Repositories\BaseRepository;

class OtpRepository extends BaseRepository implements OtpRepositoryInterface
{
    public function __construct(Otp $model)
    {
        parent::__construct($model);
    }
}
