<?php

namespace Modules\Search\Services;

use Modules\Search\Repositories\SearchRepository;


class SearchService
{
    public function __construct(
        protected SearchRepository $searchRepository
    ) {}

    public function autocomplete(string $search): array
    {
        return $this->searchRepository->autocomplete($search);
    }

    public function search(string $search): array
    {
        return $this->searchRepository->search($search);
    }
}
