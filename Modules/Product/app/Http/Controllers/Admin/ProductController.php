<?php

namespace Modules\Product\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\Product\Services\ProductService;
use Modules\Product\Http\Resources\AdminProductResource;
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
        $filters = $request->only('store_id', 'search', 'category_id');
        $products = $this->productService->getAllProducts($filters);
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
            "product" => new AdminProductResource($product),
        ], __("message.success"));
    }

    /**
     * Update a specific product.
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $product = $this->productService->updateProduct($id, $request->all());
        return $this->successResponse([
            "product" => new ProductResource($product->load('images')->refresh()),
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
        $filters = $request->only(['days', 'store_id', 'per_page', 'page']);
        $result = $this->productService->getProductsWithOffersEndingSoon($filters);

        return $this->successResponse([
            "products" => ProductResource::collection($result['products']),
            "meta" => $result['meta'],
            "pagination" => new PaginationResource($result['products']),
        ], __("message.success"));
    }

    public function stats(int $id): JsonResponse
    {
        $stats = $this->productService->getStats($id);
        return $this->successResponse([
            "stats" => $stats,
        ], __("message.success"));
    }
    public function toggleActive(int $id): JsonResponse
    {
        $product = $this->productService->toggleActive($id);
        return $this->successResponse([
            "product" => new ProductResource($product->load( [
            'store.translations',
            'store.storeSetting',
            'category.translations',
            'images',
            'price',
            'availability',
            'tags',
            'units.translations',
            'ProductNutrition',
            'productAllergen.translations',
            'pharmacyInfo.translations',
            'watermark',
            'offers',
            'requiredOptions',
            'productOptions.option.translations',
            'productOptions.optionValues.productValue.translations',
            'addOns'
        ])->refresh()),
        ], __("message.success"));
    }
}
