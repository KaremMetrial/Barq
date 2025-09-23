<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Modules\Product\Http\Requests\CreateProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Services\ProductService;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(protected ProductService $productService)
    {
    }

    /**
     * Display a listing of products.
     */
    public function index(): JsonResponse
    {
        $products = $this->productService->getAllProducts();
        return $this->successResponse([
            "products" => ProductResource::collection($products),
            "pagination" => new PaginationResource($products),
        ], __("message.success"));
    }

    /**
     * Store a newly created product.
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct($request->validated());
        return $this->successResponse([
            "product" => new ProductResource($product),
        ], __("message.success"));
    }

    /**
     * Show a specific product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProductById($id);
        return $this->successResponse([
            "product" => new ProductResource($product),
        ], __("message.success"));
    }

    /**
     * Update a specific product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->all());
        return $this->successResponse([
            "product" => new ProductResource($product),
        ], __("message.success"));
    }

    /**
     * Delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->productService->deleteProduct($id);
        return $this->successResponse(null, __("message.success"));
    }
}
