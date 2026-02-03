<?php

namespace Modules\Coupon\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Modules\Coupon\Http\Requests\CreateCouponRequest;
use Modules\Coupon\Http\Requests\UpdateCouponRequest;
use Modules\Coupon\Http\Resources\CouponResource;
use Modules\Coupon\Services\CouponService;
use Modules\Cart\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected CouponService $couponService,
        protected CartService $cartService
    )
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only('search');

        // Get cart from header to filter coupons by store
        $cart = $this->cartService->getCart();
        if ($cart?->store_id) {
            $filters['store_id'] = $cart->store_id;
        }

        // Get user's redeemed coupon codes from rewards
        $userCouponCodes = [];
        if (auth('user')->check()) {
            $userCouponCodes = \Modules\Reward\Models\RewardRedemption::where('user_id', auth('user')->id())
                ->whereNotNull('coupon_code')
                ->pluck('coupon_code')
                ->toArray();
        }

        $filters['user_coupon_codes'] = $userCouponCodes;

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
