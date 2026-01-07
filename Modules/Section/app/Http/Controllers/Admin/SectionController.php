<?php

namespace Modules\Section\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Section\Models\Section;
use App\Http\Controllers\Controller;
use Modules\Section\Services\SectionService;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Section\Http\Requests\CreateSectionRequest;
use Modules\Section\Http\Requests\UpdateSectionRequest;

class SectionController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    public function __construct(protected SectionService $sectionService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Section::class);
        $filter = $request->only('type');
        $sections = $this->sectionService->getAllSections($filter);
        return $this->successResponse([
            "sections" => SectionResource::collection($sections->load('categories'))
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateSectionRequest $request): JsonResponse
    {
        $this->authorize('create', Section::class);
        $section = $this->sectionService->createSection($request->validated());
        return $this->successResponse([
            'section' => new SectionResource($section)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $section = $this->sectionService->getSectionById($id);
        $this->authorize('view', $section);
        return $this->successResponse([
            'section' => new SectionResource($section->load('country')),
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        $section = $this->sectionService->getSectionById($id);
        $this->authorize('update', $section);
        $section = $this->sectionService->updateSection($id, $request->validated());
        return $this->successResponse([
            'section' => new SectionResource($section)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $section = $this->sectionService->getSectionById($id);
        $this->authorize('delete', $section);
        $isDeleted = $this->sectionService->deleteSection($id);
        return $this->successResponse(null, __('message.success'));
    }
}
