<?php

namespace App\Repositories\Contracts;

interface LanguageRepositoryInterface extends BaseRepositoryInterface
{
    public function getAllCodes(): array;
}
