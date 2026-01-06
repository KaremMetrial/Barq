<?php

namespace Modules\City\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\City\Services\CityService;
use Modules\City\Http\Resources\CityResource;
use Modules\City\Http\Requests\CreateCityRequest;
use Modules\City\Http\Requests\UpdateCityRequest;

class CityController extends Controller
{
    use ApiResponse;

    // Injecting CityService to manage business logic
    public function __construct(private CityService $cityService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->all();
        $cities = $this->cityService->getAllCitys($filters);
        return $this->successResponse([
            "cities" => CityResource::collection($cities),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $city = $this->cityService->getCityById($id);
        return $this->successResponse([
            "city" => new CityResource($city),
        ], __("message.success"));
    }
}
