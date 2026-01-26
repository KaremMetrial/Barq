<?php

namespace Modules\Coupon\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Modules\Coupon\Http\Requests\CreateCouponRequest;
use Modules\Coupon\Http\Requests\UpdateCouponRequest;
use Modules\Coupon\Http\Resources\CouponResource;
use Modules\Coupon\Services\CouponService;
use Modules\Coupon\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse, AuthorizesRequests;

    public function __construct(protected CouponService $couponService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Coupon::class);
        $filters = $request->all();
        $coupons = $this->couponService->getAllCoupons($filters);
        return $this->successResponse([
            "coupons" => CouponResource::collection($coupons->load('categories', 'products', 'stores')),
            "pagination" => new PaginationResource($coupons),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCouponRequest $request): JsonResponse
    {
        $this->authorize('create', Coupon::class);
        $coupon = $this->couponService->createCoupon($request->all());
        return $this->successResponse([
            "coupon" => new CouponResource($coupon),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $coupon = $this->couponService->getCouponById($id);
        $this->authorize('view', $coupon);
        return $this->successResponse([
            "coupon" => new CouponResource($coupon->load('categories', 'products', 'stores')),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
        $coupon = $this->couponService->getCouponById($id);
        $this->authorize('update', $coupon);
        $coupon = $this->couponService->updateCoupon($id, $request->all());
        return $this->successResponse([
            "coupon" => new CouponResource($coupon),
        ], __("message.success"));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $coupon = $this->couponService->getCouponById($id);
        $this->authorize('delete', $coupon);
        $deleted = $this->couponService->deleteCoupon($id);
        return $this->successResponse(null, __("message.success"));
    }
}
