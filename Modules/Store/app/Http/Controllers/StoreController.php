<?php

namespace Modules\Store\Http\Controllers;

use App\Traits\ApiResponse;
use Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreService;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Http\Requests\CreateStoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;

class StoreController extends Controller
{
    use ApiResponse;

    public function __construct(protected StoreService $StoreService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'section_id',
            'category_id',
            'has_offer',
            'sort_by',
            'rating'
        ]);
        $Stores = $this->StoreService->getAllStores($filters);
        return $this->successResponse([
            "Stores" => StoreResource::collection($Stores),
            "pagination" => new PaginationResource($Stores)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $Store = $this->StoreService->createStore($request->validated());
        return $this->successResponse([
            'Store' => new StoreResource($Store)
        ], __('message.success'));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $Store = $this->StoreService->getStoreById($id);
        return $this->successResponse([
            'Store' => new StoreResource($Store)
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreRequest $request, int $id): JsonResponse
    {
        $Store = $this->StoreService->updateStore($id, $request->validated());
        return $this->successResponse([
            'Store' => new StoreResource($Store)
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $isDeleted = $this->StoreService->deleteStore($id);
        return $this->successResponse(null, __('message.success'));
    }
    public function home(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'status',
            'section_id',
            'category_id',
            'has_offer',
            'sort_by',
            'rating'
        ]);
        $stores = $this->StoreService->getHomeStores($filters);
        return $this->successResponse([
            "topReviews" => StoreResource::collection($stores['topReviews']),
            "featured" => StoreResource::collection($stores['featured']),
            "new" => StoreResource::collection($stores['newStores']),
            "section_type" => $stores['section_type'],
            "section_label" => $stores['section_label'],
        ], __("message.success"));
    }
    public function stats()
    {
        $store = $this->StoreService->stats();
        return $this->successResponse([
            "stats" => $store,
        ], __("message.success"));
    }
}
