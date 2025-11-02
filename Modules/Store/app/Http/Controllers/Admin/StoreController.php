<?php

namespace Modules\Store\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Store\Models\Store;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Store\Services\StoreService;
use App\Http\Resources\PaginationResource;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Http\Requests\CreateStoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;
use Modules\Store\Http\Resources\Admin\StoreCollectionResource;

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
            'rating'
        ]);
        $Stores = $this->StoreService->getAdminAllStores($filters);
        return $this->successResponse([
            "Stores" => StoreCollectionResource::collection($Stores),
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
    public function stats(){
        $statsCount = $this->StoreService->adminStats();
        return $this->successResponse([
            'vendor_count' => $statsCount['vendorCount'],
            'store_count' => $statsCount['storeCount'],
            'pos_count' => $statsCount['posCount'],
            ], __('message.success'));

    }

}
