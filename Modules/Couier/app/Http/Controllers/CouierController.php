<?php

namespace Modules\Couier\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Couier\Models\Couier;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use Modules\Couier\Services\CouierService;
use Modules\Couier\Services\CourierShiftService;
use Modules\Couier\Http\Resources\CouierResource;
use Modules\Couier\Http\Resources\CourierResource;
use Modules\Couier\Http\Requests\CreateCouierRequest;
use Modules\Couier\Http\Requests\UpdateCouierRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CouierController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected CouierService $couierService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Couier::class);
        $filters = $request->all();
        $couiers = $this->couierService->getAllCouiers($filters);
        return $this->successResponse([
            "couiers" => CouierResource::collection($couiers->load('store')),
            "pagination" => new PaginationResource($couiers),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCouierRequest $request): JsonResponse
    {
        $this->authorize('create', Couier::class);
        $couier = $this->couierService->createCouier($request->all());
        return $this->successResponse([
            "couier" => new CouierResource($couier->load('store')),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $couier = $this->couierService->getCouierById($id);
        $this->authorize('view', $couier);
        return $this->successResponse([
            "couier" => new CourierResource($couier->load(['store','vehicle','zonesToCover', 'address.zone','shifts', 'attachments', 'nationalIdentity','address'])),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouierRequest $request, int $id): JsonResponse
    {
        $couier = $this->couierService->getCouierById($id);
        $this->authorize('update', $couier);
        $couier = $this->couierService->updateCouier($id, $request->all());
        return $this->successResponse([
            "couier" => new CouierResource($couier->load('store')),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $couier = $this->couierService->getCouierById($id);
        $this->authorize('delete', $couier);
        $deleted = $this->couierService->deleteCouier($id);
        return $this->successResponse(null, __("message.success"));
    }
    public function stats()
    {
        $stats = $this->couierService->stats();
        return $this->successResponse(
        $stats
        , __('message.success'));
    }
    public function home()
    {
        $data = $this->couierService->getHome();
        return $this->successResponse(
        $data
        , __('message.success'));
    }
}
