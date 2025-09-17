<?php

namespace Modules\Section\Repositories;
use Modules\Section\Models\Section;
use Modules\Section\Repositories\Contracts\SectionRepositoryInterface;
use App\Repositories\BaseRepository;
class SectionRepository extends BaseRepository implements SectionRepositoryInterface
{
    public function __construct(Section $model)
    {
        parent::__construct($model);
    }
}
