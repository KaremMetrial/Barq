<?php
namespace Modules\Tag\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Tag\Http\Requests\CreateTagRequest;
use Modules\Tag\Http\Requests\UpdateTagRequest;
use Modules\Tag\Http\Resources\TagResource;
use Modules\Tag\Services\TagService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Tag\Models\Tag;

class TagController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    // Inject the TagService to handle business logic
    public function __construct(protected TagService $tagService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Tag::class);
        $tags = $this->tagService->getAllTags();
        return $this->successResponse([
            "tags" => TagResource::collection($tags)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateTagRequest $request): JsonResponse
    {
        $this->authorize('create', Tag::class);
        $tag = $this->tagService->createTag($request->validated());
        return $this->successResponse([
            'tag' => new TagResource($tag)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $tag = $this->tagService->getTagById($id);
        $this->authorize('view', $tag);
        return $this->successResponse([
            'tag' => new TagResource($tag)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, int $id): JsonResponse
    {
        $tag = $this->tagService->getTagById($id);
        $this->authorize('update', $tag);
        $tag = $this->tagService->updateTag($id, $request->validated());
        return $this->successResponse([
            'tag' => new TagResource($tag)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $tag = $this->tagService->getTagById($id);
        $this->authorize('delete', $tag);
        $isDeleted = $this->tagService->deleteTag($id);
        return $this->successResponse(null, __('message.success'));
    }
}
