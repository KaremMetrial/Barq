<?php

namespace Modules\Zone\Http\Controllers;

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
    public function index(Request $request)
    {
        $filters = $request->all();
        $zones = $this->zoneService->getAllZones($filters);
        return $this->successResponse([
            "zones" => ZoneResource::collection($zones),
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

}
