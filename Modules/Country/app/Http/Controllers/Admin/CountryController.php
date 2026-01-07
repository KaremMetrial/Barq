<?php

namespace Modules\Country\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Modules\Country\Services\CountryService;
use Modules\Country\Http\Resources\CountryResource;
use Modules\Country\Http\Requests\StoreCountryRequest;
use Modules\Country\Http\Requests\UpdateCountryRequest;
use Modules\Country\Models\Country;

class CountryController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    public function __construct(private CountryService $countryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Country::class);
        $filters = $request->only(['search']);
        $countries = $this->countryService->getAllCountries($filters);
        return $this->successResponse([
            "countries" => CountryResource::collection($countries),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCountryRequest $request)
    {
        $this->authorize('create', Country::class);
        $country = $this->countryService->createCountry($request->validated());
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
        $this->authorize('view', $country);
        return $this->successResponse([
            "country"=> new CountryResource($country),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCountryRequest $request, $id)
    {
        $country = $this->countryService->getCountryById($id);
        $this->authorize('update', $country);
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
        $country = $this->countryService->getCountryById($id);
        $this->authorize('delete', $country);
        $deleted = $this->countryService->deleteCountry($id);
        return $this->successResponse(null, __("message.success"));
    }
}
