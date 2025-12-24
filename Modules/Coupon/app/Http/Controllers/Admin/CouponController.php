<?php

namespace Modules\Coupon\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaginationResource;
use App\Traits\ApiResponse;
use Modules\Coupon\Http\Requests\CreateCouponRequest;
use Modules\Coupon\Http\Requests\UpdateCouponRequest;
use Modules\Coupon\Http\Resources\CouponResource;
use Modules\Coupon\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(protected CouponService $couponService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $coupons = $this->couponService->getAllCoupons($filters);
        return $this->successResponse([
            "coupons" => CouponResource::collection($coupons),
            "pagination" => new PaginationResource($coupons),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCouponRequest $request): JsonResponse
    {
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
        return $this->successResponse([
            "coupon" => new CouponResource($coupon),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCouponRequest $request, int $id): JsonResponse
    {
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
        $deleted = $this->couponService->deleteCoupon($id);
        return $this->successResponse(null, __("message.success"));
    }
}
