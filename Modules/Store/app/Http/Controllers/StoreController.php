<?php

namespace Modules\Store\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Store\Http\Requests\CreateStoreRequest;
use Modules\Store\Http\Requests\UpdateStoreRequest;
use Modules\Store\Http\Resources\StoreResource;
use Modules\Store\Services\StoreService;
use Illuminate\Http\JsonResponse;
use Modules\Store\Models\Store;

class StoreController extends Controller
{
    use ApiResponse;

    public function __construct(protected StoreService $StoreService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $Stores = $this->StoreService->getAllStores();
        return $this->successResponse([
            "Stores" => StoreResource::collection($Stores)
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreRequest $request): JsonResponse
    {
        $Store = $this->StoreService->createStore($request->all());
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
        $Store = $this->StoreService->updateStore($id, $request->all());
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
}
