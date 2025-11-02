<?php

namespace Modules\Coupon\Http\Controllers;

use App\Http\Controllers\Controller;
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
        $filters = $request->only('search');
        $coupons = $this->couponService->getAllCoupons($filters);
        return $this->successResponse([
            "coupons" => CouponResource::collection($coupons),
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
}
