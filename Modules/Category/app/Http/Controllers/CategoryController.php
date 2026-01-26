<?php

namespace Modules\Category\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Category\Http\Requests\CreateCategoryRequest;
use Modules\Category\Http\Requests\UpdateCategoryRequest;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Category\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;
    public function __construct(protected CategoryService $categoryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $categories = $this->categoryService->getAllCategories($filters);
        return $this->successResponse([
            "categories" => CategoryResource::collection($categories->load('children')),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->getCategoryById($id);
        return $this->successResponse([
            "category" => new CategoryResource($category),
            // 'subcategories' => CategoryResource::collection($category->children),
        ], __("message.success"));
    }
}
