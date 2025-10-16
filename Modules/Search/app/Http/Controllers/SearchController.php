<?php

namespace Modules\Search\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Search\Services\SearchService;

class SearchController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected SearchService $searchService
    ) {}

    public function autocomplete(Request $request)
    {
        $search = $request->input('search', '');

        $result = $this->searchService->autocomplete($search);

        return $this->successResponse([
            'search' => $result,
        ], __('message.success'));
    }

    public function search(Request $request)
    {
        $search = $request->input('search', '');

        $result = $this->searchService->search($search);

        return $this->successResponse([
            'search' => $result,
        ], __('message.success'));
    }
        public function getTopSearchLogs(Request $request)
    {
        $topSearchLogs = $this->searchService->getTopSearchLogs($request->get('limit', 10));

        return $this->successResponse([
            'topSearchLogs' => $topSearchLogs,
        ], __('message.success'));
    }

}
