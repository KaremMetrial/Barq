<?php

namespace Modules\Zone\Http\Controllers\Admin;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\Zone\Services\ZoneService;
use App\Http\Resources\PaginationResource;
use Modules\Zone\Http\Resources\ZoneResource;
use Modules\Zone\Http\Requests\CreateZoneRequest;
use Modules\Zone\Http\Requests\UpdateZoneRequest;

class ZoneController extends Controller
{
    use ApiResponse;

    // Injecting ZoneService to manage business logic
    public function __construct(private ZoneService $zoneService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zones = $this->zoneService->getAllZones();
        return $this->successResponse([
            "zones" => ZoneResource::collection($zones),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateZoneRequest $request)
    {
        $zone = $this->zoneService->createZone($request->all());
        return $this->successResponse([
            "zone" => new ZoneResource($zone),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $zone = $this->zoneService->getZoneById($id);
        return $this->successResponse([
            "zone" => new ZoneResource($zone),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateZoneRequest $request, $id)
    {
        $zone = $this->zoneService->updateZone($id, $request->all());
        return $this->successResponse([
            "zone" => new ZoneResource($zone),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $deleted = $this->zoneService->deleteZone($id);
        return $this->successResponse(null, __("message.success"));
    }
}
