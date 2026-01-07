<?php

namespace Modules\Governorate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Governorate\Services\GovernorateService;
use Modules\Governorate\Http\Requests\StoreGovernorateRequest;
use Modules\Governorate\Http\Requests\UpdateGovernorateRequest;
use Modules\Governorate\Http\Resources\GovernorateResource;
use Modules\Governorate\Models\Governorate;

class GovernorateController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    // Injecting GovernorateService to manage business logic
    public function __construct(private GovernorateService $governorateService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Governorate::class);
        $governorates = $this->governorateService->getAllGovernorates(request()->all());
        return $this->successResponse([
            "governorates" => GovernorateResource::collection($governorates->load('country')),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGovernorateRequest $request)
    {
        $this->authorize('create', Governorate::class);
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
        $this->authorize('view', $governorate);
        return $this->successResponse([
            "governorate"=> new GovernorateResource($governorate->load('country')),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGovernorateRequest $request, $id)
    {
        $governorate = $this->governorateService->getGovernorateById($id);
        $this->authorize('update', $governorate);
        $governorate = $this->governorateService->updateGovernorate($id, $request->all());
        return $this->successResponse([
            "governorate"=> new GovernorateResource($governorate->load('country')),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $governorate = $this->governorateService->getGovernorateById($id);
        $this->authorize('delete', $governorate);
        $deleted = $this->governorateService->deleteGovernorate($id);
        return $this->successResponse(null, __("message.success"));
    }
}
