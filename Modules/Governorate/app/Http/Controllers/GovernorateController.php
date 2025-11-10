<?php

namespace Modules\Governorate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Governorate\Services\GovernorateService;
use Modules\Governorate\Http\Requests\StoreGovernorateRequest;
use Modules\Governorate\Http\Requests\UpdateGovernorateRequest;
use Modules\Governorate\Http\Resources\GovernorateResource;

class GovernorateController extends Controller
{
    use ApiResponse;

    // Injecting GovernorateService to manage business logic
    public function __construct(private GovernorateService $governorateService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $governorates = $this->governorateService->getAllGovernorates();
        return $this->successResponse([
            "governorates" => GovernorateResource::collection($governorates->load('country')),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGovernorateRequest $request)
    {
        $governorate = $this->governorateService->createGovernorate($request->all());
        return $this->successResponse([
            "governorate" => new GovernorateResource($governorate),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $governorate = $this->governorateService->getGovernorateById($id);
        return $this->successResponse([
            "governorate"=> new GovernorateResource($governorate),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGovernorateRequest $request, $id)
    {
        $governorate = $this->governorateService->updateGovernorate($id, $request->all());
        return $this->successResponse([
            "governorate"=> new GovernorateResource($governorate),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->governorateService->deleteGovernorate($id);
        return $this->successResponse(null, __("message.success"));
    }
}
