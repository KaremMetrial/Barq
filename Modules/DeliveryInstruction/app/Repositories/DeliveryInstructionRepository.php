<?php

namespace Modules\DeliveryInstruction\Repositories;

use Modules\DeliveryInstruction\Models\DeliveryInstruction;
use Modules\DeliveryInstruction\Repositories\Contracts\DeliveryInstructionRepositoryInterface;
use App\Repositories\BaseRepository;

class DeliveryInstructionRepository extends BaseRepository implements DeliveryInstructionRepositoryInterface
{
    public function __construct(DeliveryInstruction $model)
    {
        parent::__construct($model);
    }
}
