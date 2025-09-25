<?php

namespace Modules\Couier\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Couier\Http\Requests\CreateCouierRequest;
use Modules\Couier\Http\Requests\UpdateCouierRequest;
use Modules\Couier\Http\Resources\CouierResource;
use Modules\Couier\Services\CouierService;
use Illuminate\Http\JsonResponse;

class CouierController extends Controller
{
    use ApiResponse;

    public function __construct(protected CouierService $couierService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $couiers = $this->couierService->getAllCouiers();
        return $this->successResponse([
            "couiers" => CouierResource::collection($couiers),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCouierRequest $request): JsonResponse
    {
        $couier = $this->couierService->createCouier($request->all());
        return $this->successResponse([
            "couier" => new CouierResource($couier),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $couier = $this->couierService->getCouierById($id);
        return $this->successResponse([
            "couier" => new CouierResource($couier),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouierRequest $request, int $id): JsonResponse
    {
        $couier = $this->couierService->updateCouier($id, $request->all());
        return $this->successResponse([
            "couier" => new CouierResource($couier),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->couierService->deleteCouier($id);
        return $this->successResponse(null, __("message.success"));
    }
}
