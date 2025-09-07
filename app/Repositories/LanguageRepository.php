<?php

namespace App\Repositories;

use App\Models\Language;
use App\Repositories\Contracts\LanguageRepositoryInterface;

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
