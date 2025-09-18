<?php

namespace Modules\Tag\Repositories;
use Modules\Tag\Models\Tag;
use Modules\Tag\Repositories\Contracts\TagRepositoryInterface;
use App\Repositories\BaseRepository;
class TagRepository extends BaseRepository implements TagRepositoryInterface
{
    public function __construct(Tag $model)
    {
        parent::__construct($model);
    }
}
