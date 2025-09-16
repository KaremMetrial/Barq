<?php

namespace Modules\Language\Repositories;

use App\Models\Language;
use Modules\Language\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\BaseRepository;
class LanguageRepository extends BaseRepository implements LanguageRepositoryInterface
{
    public function __construct(Language $model)
    {
        parent::__construct($model);
    }
    public function getAllCodes(): array
    {
        return $this->model->pluck('code')->toArray();
    }
}
