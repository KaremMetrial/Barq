<?php

namespace Modules\Banner\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Banner\Http\Requests\CreateBannerRequest;
use Modules\Banner\Http\Requests\UpdateBannerRequest;
use Modules\Banner\Http\Resources\BannerResource;
use Modules\Banner\Services\BannerService;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    use ApiResponse;

    public function __construct(protected BannerService $bannerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $banners = $this->bannerService->getAllBanners();
        return $this->successResponse([
            "banners" => BannerResource::collection($banners),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateBannerRequest $request): JsonResponse
    {
        $banner = $this->bannerService->createBanner($request->all());
        return $this->successResponse([
            "banner" => new BannerResource($banner),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $banner = $this->bannerService->getBannerById($id);
        return $this->successResponse([
            "banner" => new BannerResource($banner),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, int $id): JsonResponse
    {
        $banner = $this->bannerService->updateBanner($id, $request->all());
        return $this->successResponse([
            "banner" => new BannerResource($banner),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->bannerService->deleteBanner($id);
        return $this->successResponse(null, __("message.success"));
    }
    public function getindex()
    {
        $banners = $this->bannerService->getIndex();
        return $this->successResponse([
            "banners" => BannerResource::collection($banners),
        ], __("message.success"));
    }
}
