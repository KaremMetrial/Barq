<?php

namespace Modules\Unit\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Unit\Services\UnitService;
use Modules\Unit\Http\Resources\UnitResource;
use Modules\Unit\Http\Requests\CreateUnitRequest;
use Modules\Unit\Http\Requests\UpdateUnitRequest;
use Modules\Unit\Models\Unit;

class UnitController extends Controller
{
    use ApiResponse, AuthorizesRequests;
    public function __construct(protected UnitService $unitService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);
        $units = $this->unitService->getAllUnits();
        return $this->successResponse([
            "units" => UnitResource::collection($units)
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateUnitRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);
        $unit = $this->unitService->createUnit($request->all());
        return $this->successResponse([
            "unit" => new UnitResource($unit)
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $unit = $this->unitService->getUnitById($id);
        $this->authorize('view', $unit);
        return $this->successResponse([
            "unit" => new UnitResource($unit)
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
        $unit = $this->unitService->getUnitById($id);
        $this->authorize('update', $unit);
        $unit = $this->unitService->updateUnit($id, $request->all());
        return $this->successResponse([
            "unit" => new UnitResource($unit)
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $unit = $this->unitService->getUnitById($id);
        $this->authorize('delete', $unit);
        $deleted = $this->unitService->deleteUnit($id);
        return $this->successResponse(null, __("message.success"));
    }
}
