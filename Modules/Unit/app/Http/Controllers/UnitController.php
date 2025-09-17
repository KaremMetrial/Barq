<?php

namespace Modules\Unit\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Unit\Services\UnitService;
use Modules\Unit\Http\Resources\UnitResource;
use Modules\Unit\Http\Requests\CreateUnitRequest;
use Modules\Unit\Http\Requests\UpdateUnitRequest;

class UnitController extends Controller
{
    use ApiResponse;
    public function __construct(protected UnitService $unitService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
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
        $unit = $this->unitService->createUnit($request->validated());
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
        return $this->successResponse([
            "unit" => new UnitResource($unit)
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, int $id): JsonResponse
    {
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
        $deleted = $this->unitService->deleteUnit($id);
        return $this->successResponse(null, __("message.success"));
    }
}
