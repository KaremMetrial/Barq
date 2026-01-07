<?php

namespace Modules\Category\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Category\Http\Requests\CreateCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Category\Services\CategoryService;
use Modules\Category\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    public function __construct(protected CategoryService $categoryService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Category::class);
        $filters = $request->all();
        $categories = $this->categoryService->getAllCountries($filters);
        return $this->successResponse([
            "categories"=> CategoryResource::collection($categories->load('children')),
        ],__("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $category = $this->categoryService->createCategory($request->all());
        return $this->successResponse([
            "category"=> new CategoryResource($category),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('view', $category);
        return $this->successResponse([
            "category"=> new CategoryResource($category),
            // 'subcategories' => CategoryResource::collection($category->children),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('update', $category);
        $category = $this->categoryService->updateCategory($id, $request->all());
        return $this->successResponse([
            "category"=> new CategoryResource($category),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        $this->authorize('delete', $category);
        $deleted = $this->categoryService->deleteCategory($id);
        return $this->successResponse(null, __("message.success"));
    }
}
