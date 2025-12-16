<?php

namespace Modules\Product\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\Product\Services\ProductService;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Requests\CreateProductRequest;
use Modules\Product\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(protected ProductService $productService) {}

    /**
     * Display a listing of products.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only('store_id', 'category_id', 'search');
        $products = $this->productService->getAllProducts($filters);
        return $this->successResponse([
            "products" => ProductResource::collection($products),
            "pagination" => new PaginationResource($products),
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
    public function home(): JsonResponse
    {
        $products = $this->productService->getHomeProducts();
        return $this->successResponse([
            "topReviews" => ProductResource::collection($products['topReviews']),
            "featured" => ProductResource::collection($products['featured']),
            "newProduct" => ProductResource::collection($products['newProduct']),
        ], __("message.success"));
    }
    public function groupedProductsByStore(Request $request, int $storeId): JsonResponse
    {
        $result = $this->productService->getGroupedProductsByStore($storeId, $request->all());

        $grouped = collect($result['grouped_products'])->map(function ($products) {
            return [
                'products' => ProductResource::collection($products),
            ];
        });

        return $this->successResponse([
            ...$grouped,
            'pagination' => new PaginationResource($result['paginator']),
        ], __("message.success"));
    }
    public function getOffersEndingSoon(Request $request): JsonResponse
    {
        $filters = $request->only(['days', 'store_id', 'per_page', 'page', 'section_id', 'category_id']);
        $result = $this->productService->getProductsWithOffersEndingSoon($filters);

        return $this->successResponse([
            "products" => $result['products']->isEmpty()
                ? []
                : ProductResource::collection($result['products']),
            "meta" => $result['products']->isEmpty() ? null : $result['meta'],
            "pagination" => new PaginationResource($result['products']),
        ], __("message.success"));
    }
}
