<?php

namespace Modules\Page\Repositories;
use Modules\Page\Models\Page;
use Modules\Page\Repositories\Contracts\PageRepositoryInterface;
use App\Repositories\BaseRepository;
class PageRepository extends BaseRepository implements PageRepositoryInterface
{
    public function __construct(Page $model)
    {
        parent::__construct($model);
    }
}
