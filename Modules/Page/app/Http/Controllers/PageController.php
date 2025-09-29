<?php

namespace Modules\Page\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Page\Services\PageService;
use Modules\Page\Http\Resources\PageResource;
use Modules\Page\Http\Requests\CreatePageRequest;
use Modules\Page\Http\Requests\UpdatePageRequest;

class PageController extends Controller
{
    use ApiResponse;

    public function __construct(private PageService $pageService) {}

    /**
     * Display a listing of pages.
     */
    public function index()
    {
        $pages = $this->pageService->getAllPages();
        return $this->successResponse([
            'pages' => PageResource::collection($pages),
        ], __('message.success'));
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(CreatePageRequest $request)
    {
        $page = $this->pageService->createPage($request->validated());
        return $this->successResponse([
            'page' => new PageResource($page),
        ], __('message.success'));
    }

    /**
     * Display the specified page.
     */
    public function show($id)
    {
        $page = $this->pageService->getPageById($id);
        return $this->successResponse([
            'page' => new PageResource($page),
        ], __('message.success'));
    }

    /**
     * Update the specified page in storage.
     */
    public function update(UpdatePageRequest $request, $id)
    {
        $page = $this->pageService->updatePage($id, $request->validated());
        return $this->successResponse([
            'page' => new PageResource($page),
        ], __('message.success'));
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy($id)
    {
        $this->pageService->deletePage($id);
        return $this->successResponse(null, __('message.success'));
    }
}
