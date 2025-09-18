<?php

namespace Modules\Country\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Country\Services\CountryService;
use Modules\Country\Http\Requests\StoreCountryRequest;
use Modules\Country\Http\Requests\UpdateCountryRequest;
use Modules\Country\Http\Resources\CountryResource;

class CountryController extends Controller
{
    use ApiResponse;
    public function __construct(private CountryService $countryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = $this->countryService->getAllCountries();
        return $this->successResponse([
            "countries" => CountryResource::collection($countries),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $country = $this->countryService->createCountry($request->all());
        return $this->successResponse([
            "country" => new CountryResource($country),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $country = $this->countryService->getCountryById($id);
        return $this->successResponse([
            "country"=> new CountryResource($country),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, $id)
    {
        $country = $this->countryService->updateCountry($id, $request->all());
        return $this->successResponse([
            "country"=> new CountryResource($country),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->countryService->deleteCountry($id);
        return $this->successResponse(null, __("message.success"));
    }
}
