<?php

namespace Modules\PosShift\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\PosShift\Services\PosShiftService;
use Modules\PosShift\Http\Resources\PosShiftResource;
use Modules\PosShift\Http\Requests\CreatePosShiftRequest;
use Modules\PosShift\Http\Requests\UpdatePosShiftRequest;

class PosShiftController extends Controller
{
    use ApiResponse;

    // Injecting PosShiftService to manage business logic
    public function __construct(private PosShiftService $posShiftService) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
        return $this->successResponse([
            "pos_shift" => new PosShiftResource($posShift),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePosShiftRequest $request, $id)
    {
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
        $deleted = $this->posShiftService->deletePosShift($id);
        return $this->successResponse(null, __("message.success"));
    }
}
