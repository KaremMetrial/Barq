<?php

namespace Modules\Search\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use Modules\Search\Repositories\SearchRepository;


class SearchService
{
    public function __construct(
        protected SearchRepository $searchRepository
    ) {}

    public function autocomplete(string $search): array
    {
        $this->logSearch($search);

        return $this->searchRepository->autocomplete($search);
    }

    public function search(string $search): array
    {
        $this->logSearch($search);
        return $this->searchRepository->search($search);
    }
    protected function logSearch(string $searchTerm, ?int $categoryId = null): void
    {
        DB::table('category_search_logs')->insert([
            'user_id'      => Auth::id(),
            'category_id'  => $categoryId,
            'search_term'  => $searchTerm,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }
    public function getTopSearchLogs(int $limit = 10): array
    {
        return DB::table('category_search_logs')
            ->select('search_term', DB::raw('COUNT(*) as count'))
            ->groupBy('search_term')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
