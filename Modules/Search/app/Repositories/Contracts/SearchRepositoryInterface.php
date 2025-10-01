<?php

namespace Modules\Search\Repositories\Contracts;

interface SearchRepositoryInterface
{
    public function autocomplete(string $search): array;
    public function search(string $search): array;
}
