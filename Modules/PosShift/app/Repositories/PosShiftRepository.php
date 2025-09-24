<?php

namespace Modules\PosShift\Repositories;
use Modules\PosShift\Models\PosShift;
use Modules\PosShift\Repositories\Contracts\PosShiftRepositoryInterface;
use App\Repositories\BaseRepository;
class PosShiftRepository extends BaseRepository implements PosShiftRepositoryInterface
{
    public function __construct(PosShift $model)
    {
        parent::__construct($model);
    }
}
