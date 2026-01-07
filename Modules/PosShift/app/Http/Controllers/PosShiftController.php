<?php

namespace Modules\PosShift\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\PosShift\Services\PosShiftService;
use Modules\PosShift\Http\Resources\PosShiftResource;
use Modules\PosShift\Http\Requests\CreatePosShiftRequest;
use Modules\PosShift\Http\Requests\UpdatePosShiftRequest;
use Modules\PosShift\Models\PosShift;

class PosShiftController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    // Injecting PosShiftService to manage business logic
    public function __construct(private PosShiftService $posShiftService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', PosShift::class);
        $posShifts = $this->posShiftService->getAllPosShifts();
        return $this->successResponse([
            "pos_shifts" => PosShiftResource::collection($posShifts),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreatePosShiftRequest $request)
    {
        $this->authorize('create', PosShift::class);
        $posShift = $this->posShiftService->createPosShift($request->all());
        return $this->successResponse([
            "pos_shift" => new PosShiftResource($posShift),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        $posShift = $this->posShiftService->getPosShiftById($id);
        $this->authorize('view', $posShift);
        return $this->successResponse([
            "pos_shift" => new PosShiftResource($posShift),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePosShiftRequest $request, $id)
    {
        $posShift = $this->posShiftService->getPosShiftById($id);
        $this->authorize('update', $posShift);
        $posShift = $this->posShiftService->updatePosShift($id, $request->all());
        return $this->successResponse([
            "pos_shift" => new PosShiftResource($posShift),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $posShift = $this->posShiftService->getPosShiftById($id);
        $this->authorize('delete', $posShift);
        $deleted = $this->posShiftService->deletePosShift($id);
        return $this->successResponse(null, __("message.success"));
    }
}
