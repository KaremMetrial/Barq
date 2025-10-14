<?php

namespace Modules\Country\Http\Controllers;

use App\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Country\Services\CountryService;
use Modules\Country\Http\Resources\CountryResource;

class CountryController extends Controller
{
    use ApiResponse;
    public function __construct(private CountryService $countryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $countries = $this->countryService->getAllCountries($filters);
        return $this->successResponse([
            "countries" => CountryResource::collection($countries),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $country = $this->countryService->getCountryById($id);
        return $this->successResponse([
            "country" => new CountryResource($country),
        ], __("message.success"));
    }
}
