<?php

namespace Modules\Ad\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Ad\Http\Requests\CreateAdRequest;
use Modules\Ad\Http\Requests\UpdateAdRequest;
use Modules\Ad\Http\Resources\AdResource;
use Modules\Ad\Services\AdService;
use Modules\Ad\Models\Ad;
use Illuminate\Http\JsonResponse;

class AdController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected AdService $adService)
    {
    }

    /**
     * Display a listing of the ads.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Ad::class);
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
        $this->authorize('create', Ad::class);
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
        $this->authorize('view', $ad);

        return $this->successResponse([
            "ad" => new AdResource($ad),
        ], __("message.success"));
    }

    /**
     * Update the specified ad.
     */
    public function update(UpdateAdRequest $request, int $id): JsonResponse
    {
        $ad = $this->adService->getAdById($id);
        $this->authorize('update', $ad);
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
        $ad = $this->adService->getAdById($id);
        $this->authorize('delete', $ad);
        $this->adService->deleteAd($id);

        return $this->successResponse(null, __("message.success"));
    }
}
