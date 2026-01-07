<?php

namespace Modules\Vehicle\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Vehicle\Services\VehicleService;
use Modules\Vehicle\Http\Resources\VehicleResource;
use Modules\Vehicle\Http\Requests\CreateVehicleRequest;
use Modules\Vehicle\Http\Requests\UpdateVehicleRequest;
use Modules\Vehicle\Models\Vehicle;

class VehicleController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected VehicleService $vehicleService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Vehicle::class);
        $vehicles = $this->vehicleService->getAllVehicles();
        return $this->successResponse([
            "vehicles" => VehicleResource::collection($vehicles)
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateVehicleRequest $request): JsonResponse
    {
        $this->authorize('create', Vehicle::class);
        $vehicle = $this->vehicleService->createVehicle($request->all());
        return $this->successResponse([
            "vehicle" => new VehicleResource($vehicle)
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->getVehicleById($id);
        $this->authorize('view', $vehicle);
        return $this->successResponse([
            "vehicle" => new VehicleResource($vehicle)
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVehicleRequest $request, int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->getVehicleById($id);
        $this->authorize('update', $vehicle);
        $vehicle = $this->vehicleService->updateVehicle($id, $request->all());
        return $this->successResponse([
            "vehicle" => new VehicleResource($vehicle)
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $vehicle = $this->vehicleService->getVehicleById($id);
        $this->authorize('delete', $vehicle);
        $deleted = $this->vehicleService->deleteVehicle($id);
        return $this->successResponse(null, __("message.success"));
    }
}
