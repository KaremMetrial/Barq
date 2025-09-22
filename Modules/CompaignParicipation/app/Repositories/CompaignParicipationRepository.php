<?php

namespace Modules\CompaignParicipation\Repositories;
use Modules\CompaignParicipation\Models\CompaignParicipation;
use Modules\CompaignParicipation\Repositories\Contracts\CompaignParicipationRepositoryInterface;
use App\Repositories\BaseRepository;
class CompaignParicipationRepository extends BaseRepository implements CompaignParicipationRepositoryInterface
{
    public function __construct(CompaignParicipation $model)
    {
        parent::__construct($model);
    }
}
