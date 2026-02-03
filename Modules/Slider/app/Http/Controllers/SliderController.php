<?php

namespace Modules\Slider\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Slider\Http\Requests\CreateSliderRequest;
use Modules\Slider\Http\Requests\UpdateSliderRequest;
use Modules\Slider\Http\Resources\SliderResource;
use Modules\Slider\Services\SliderService;
use Modules\Slider\Models\Slider;
use Illuminate\Http\JsonResponse;

class SliderController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected SliderService $sliderService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Slider::class);
        $filters = request()->all();
        $sliders = $this->sliderService->getAllSliders($filters);
        return $this->successResponse([
            "sliders" => SliderResource::collection($sliders)
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateSliderRequest $request): JsonResponse
    {
        $this->authorize('create', Slider::class);
        $slider = $this->sliderService->createSlider($request->all());
        return $this->successResponse([
            "slider" => new SliderResource($slider),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $slider = $this->sliderService->getSliderById($id);
        $this->authorize('view', $slider);
        return $this->successResponse([
            "slider" => new SliderResource($slider),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSliderRequest $request, int $id): JsonResponse
    {
        $slider = $this->sliderService->getSliderById($id);
        $this->authorize('update', $slider);
        $slider = $this->sliderService->updateSlider($id, $request->all());
        return $this->successResponse([
            "slider" => new SliderResource($slider),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $slider = $this->sliderService->getSliderById($id);
        $this->authorize('delete', $slider);
        $deleted = $this->sliderService->deleteSlider($id);
        return $this->successResponse(null, __("message.success"));
    }

    /**
     * Get active sliders for public access.
     */
    public function getindex()
    {
        $sliders = $this->sliderService->getIndex();
        return $this->successResponse([
            "sliders" => SliderResource::collection($sliders),
        ], __("message.success"));
    }
}