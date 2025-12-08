<?php

namespace Modules\WorkingDay\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Modules\WorkingDay\Http\Requests\CreateWorkingDayRequest;
use Modules\WorkingDay\Http\Requests\UpdateWorkingDayRequest;
use Modules\WorkingDay\Http\Resources\WorkingDayResource;
use Modules\WorkingDay\Services\WorkingDayService;
use Illuminate\Http\Request;

class WorkingDayController extends Controller
{
    use ApiResponse;

    public function __construct(protected WorkingDayService $workingDayService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only('store_id');
        $workingDays = $this->workingDayService->getAllWorkingDays($filters);
        return $this->successResponse([
            'working_days' => WorkingDayResource::collection($workingDays),
        ], __('message.success'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateWorkingDayRequest $request): JsonResponse
    {
        $workingDay = $this->workingDayService->createWorkingDay($request->validated());
        return $this->successResponse([
            'working_day' => new WorkingDayResource($workingDay),
        ], __('message.success'));
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $workingDay = $this->workingDayService->getWorkingDayById($id);
        return $this->successResponse([
            'working_day' => new WorkingDayResource($workingDay),
        ], __('message.success'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWorkingDayRequest $request, int $id): JsonResponse
    {
        $workingDay = $this->workingDayService->updateWorkingDay($id, $request->validated());
        return $this->successResponse([
            'working_day' => new WorkingDayResource($workingDay),
        ], __('message.success'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->workingDayService->deleteWorkingDay($id);
        return $this->successResponse(null, __('message.success'));
    }
}
