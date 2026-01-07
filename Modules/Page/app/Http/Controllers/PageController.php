<?php

namespace Modules\Page\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Page\Services\PageService;
use Modules\Page\Http\Resources\PageResource;
use Modules\Page\Http\Requests\CreatePageRequest;
use Modules\Page\Http\Requests\UpdatePageRequest;
use Modules\Page\Models\Page;

class PageController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(private PageService $pageService) {}

    /**
     * Display a listing of pages.
     */
    public function index()
    {
        $this->authorize('viewAny', Page::class);
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
        $this->authorize('create', Page::class);
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
        // Allow public access to view published pages, but restrict admin access to policy
        if (auth('admin')->check() || auth('vendor')->check() || auth('courier')->check()) {
            $this->authorize('view', $page);
        }
        return $this->successResponse([
            'page' => new PageResource($page),
        ], __('message.success'));
    }

    /**
     * Update the specified page in storage.
     */
    public function update(UpdatePageRequest $request, $id)
    {
        $page = $this->pageService->getPageById($id);
        $this->authorize('update', $page);
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
        $page = $this->pageService->getPageById($id);
        $this->authorize('delete', $page);
        $this->pageService->deletePage($id);
        return $this->successResponse(null, __('message.success'));
    }
}
