<?php

namespace Modules\Banner\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Banner\Http\Requests\CreateBannerRequest;
use Modules\Banner\Http\Requests\UpdateBannerRequest;
use Modules\Banner\Http\Resources\BannerResource;
use Modules\Banner\Services\BannerService;
use Modules\Banner\Models\Banner;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected BannerService $bannerService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Banner::class);
        $filters = request()->all();
        $banners = $this->bannerService->getAllBanners($filters);
        return $this->successResponse([
            "banners" => BannerResource::collection($banners),
            'pagination' => new PaginationResource($banners),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateBannerRequest $request): JsonResponse
    {
        $this->authorize('create', Banner::class);
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
        $this->authorize('view', $banner);
        return $this->successResponse([
            "banner" => new BannerResource($banner->load('city')),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBannerRequest $request, int $id): JsonResponse
    {
        $banner = $this->bannerService->getBannerById($id);
        $this->authorize('update', $banner);
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
        $banner = $this->bannerService->getBannerById($id);
        $this->authorize('delete', $banner);
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
