<?php

namespace Modules\Language\Repositories\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
interface LanguageRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllCodes(): array;
}
