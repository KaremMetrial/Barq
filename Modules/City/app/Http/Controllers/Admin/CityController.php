<?php

namespace Modules\City\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\City\Services\CityService;
use Modules\City\Http\Resources\CityResource;
use Modules\City\Http\Requests\CreateCityRequest;
use Modules\City\Http\Requests\UpdateCityRequest;
use Modules\City\Models\City;

class CityController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    // Injecting CityService to manage business logic
    public function __construct(private CityService $cityService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', City::class);
        $cities = $this->cityService->getAllCitys(request()->all());
        return $this->successResponse([
            "cities" => CityResource::collection($cities),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCityRequest $request)
    {
        $this->authorize('create', City::class);
        $city = $this->cityService->createCity($request->all());
        return $this->successResponse([
            "city" => new CityResource($city),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $city = $this->cityService->getCityById($id);
        $this->authorize('view', $city);
        return $this->successResponse([
            "city" => new CityResource($city->load('governorate')),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCityRequest $request, $id)
    {
        $city = $this->cityService->getCityById($id);
        $this->authorize('update', $city);
        $city = $this->cityService->updateCity($id, $request->all());
        return $this->successResponse([
            "city" => new CityResource($city->load('governorate')),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $city = $this->cityService->getCityById($id);
        $this->authorize('delete', $city);
        $deleted = $this->cityService->deleteCity($id);
        return $this->successResponse(null, __("message.success"));
    }
}
