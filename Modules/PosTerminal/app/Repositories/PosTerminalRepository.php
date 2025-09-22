<?php

namespace Modules\PosTerminal\Repositories;
use Modules\PosTerminal\Models\PosTerminal;
use Modules\PosTerminal\Repositories\Contracts\PosTerminalRepositoryInterface;
use App\Repositories\BaseRepository;
class PosTerminalRepository extends BaseRepository implements PosTerminalRepositoryInterface
{
    public function __construct(PosTerminal $model)
    {
        parent::__construct($model);
    }
}
