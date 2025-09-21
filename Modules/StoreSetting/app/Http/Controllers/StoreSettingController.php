<?php

namespace Modules\StoreSetting\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\StoreSetting\Http\Requests\CreateStoreSettingRequest;
use Modules\StoreSetting\Http\Requests\UpdateStoreSettingRequest;
use Modules\StoreSetting\Http\Resources\StoreSettingResource;
use Modules\StoreSetting\Services\StoreSettingService;

class StoreSettingController extends Controller
{
    use ApiResponse;

    public function __construct(protected StoreSettingService $storeSettingService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $settings = $this->storeSettingService->getAllStoreSettings();
        return $this->successResponse([
            'store_settings' => StoreSettingResource::collection($settings),
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateStoreSettingRequest $request): JsonResponse
    {
        $setting = $this->storeSettingService->createStoreSetting($request->validated());
        return $this->successResponse([
            'store_setting' => new StoreSettingResource($setting),
        ], __('message.success'));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $setting = $this->storeSettingService->getStoreSettingById($id);
        return $this->successResponse([
            'store_setting' => new StoreSettingResource($setting),
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateStoreSettingRequest $request, int $id): JsonResponse
    {
        $setting = $this->storeSettingService->updateStoreSetting($id, $request->validated());
        return $this->successResponse([
            'store_setting' => new StoreSettingResource($setting),
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->storeSettingService->deleteStoreSetting($id);
        return $this->successResponse(null, __('message.success'));
    }
}
