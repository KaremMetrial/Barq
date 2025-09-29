<?php

namespace Modules\Ad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Ad\Http\Requests\CreateAdRequest;
use Modules\Ad\Http\Requests\UpdateAdRequest;
use Modules\Ad\Http\Resources\AdResource;
use Modules\Ad\Services\AdService;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    use ApiResponse;

    public function __construct(protected AdService $adService)
    {
    }

    /**
     * Display a listing of the ads.
     */
    public function index(): JsonResponse
    {
        $ads = $this->adService->getAllAds();

        return $this->successResponse([
            "ads" => AdResource::collection($ads),
        ], __("message.success"));
    }

    /**
     * Store a newly created ad.
     */
    public function store(CreateAdRequest $request): JsonResponse
    {
        $ad = $this->adService->createAd($request->all());

        return $this->successResponse([
            "ad" => new AdResource($ad),
        ], __("message.success"));
    }

    /**
     * Display the specified ad.
     */
    public function show(int $id): JsonResponse
    {
        $ad = $this->adService->getAdById($id);

        return $this->successResponse([
            "ad" => new AdResource($ad),
        ], __("message.success"));
    }

    /**
     * Update the specified ad.
     */
    public function update(UpdateAdRequest $request, int $id): JsonResponse
    {
        $ad = $this->adService->updateAd($id, $request->all());

        return $this->successResponse([
            "ad" => new AdResource($ad),
        ], __("message.success"));
    }

    /**
     * Remove the specified ad from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->adService->deleteAd($id);

        return $this->successResponse(null, __("message.success"));
    }
}
