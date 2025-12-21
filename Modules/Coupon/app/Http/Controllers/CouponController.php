<?php

namespace Modules\Coupon\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Coupon\Models\Coupon;
use Modules\Coupon\Services\CouponService;
use Modules\Coupon\Http\Resources\CouponResource;
use Modules\Coupon\Http\Requests\CreateCouponRequest;
use Modules\Coupon\Http\Requests\UpdateCouponRequest;
use Modules\Coupon\Http\Resources\CouponCollection;
use Modules\Coupon\Http\Requests\CalculateDiscountRequest;

class CouponController extends Controller
{
    public function __construct(
        protected CouponService $CouponService
    ) {}
    
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->all();
            
            // Check if user wants their specific coupons
            if (auth('sanctum')->check() && ($request->header('Cart-Key') || $request->input('cart_key'))) {
                $coupons = $this->CouponService->getUserCoupons($filters);
                return response()->json([
                    'data' => CouponResource::collection($coupons),
                    'message' => 'User coupons retrieved successfully'
                ]);
            }
            
            // Regular coupon listing
            $coupons = $this->CouponService->getAllCoupons($filters);
            return response()->json([
                'data' => new CouponCollection($coupons),
                'message' => 'Coupons retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function store(CreateCouponRequest $request): JsonResponse
    {
        try {
            $coupon = $this->CouponService->createCoupon($request->validated());
            return response()->json([
                'data' => new CouponResource($coupon),
                'message' => 'Coupon created successfully'
            ], 201);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function show(Coupon $coupon): JsonResponse
    {
        try {
            return response()->json([
                'data' => new CouponResource($coupon),
                'message' => 'Coupon retrieved successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $updatedCoupon = $this->CouponService->updateCoupon($coupon->id, $request->validated());
            return response()->json([
                'data' => new CouponResource($updatedCoupon),
                'message' => 'Coupon updated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $this->CouponService->deleteCoupon($coupon->id);
            return response()->json([
                'message' => 'Coupon deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Calculate discount for a given coupon and order amount
     *
     * @param CalculateDiscountRequest $request
     * @return JsonResponse
     */
    public function calculateDiscount(CalculateDiscountRequest $request): JsonResponse
    {
        try {
            $coupon = Coupon::where('code', $request->code)->first();
            
            if (!$coupon) {
                return response()->json([
                    'message' => 'Coupon not found'
                ], 404);
            }
            
            if (!$coupon->isValid()) {
                return response()->json([
                    'message' => 'Coupon is not valid'
                ], 400);
            }
            
            $discountValue = $this->CouponService->calculateDiscount($coupon, $request->order_amount);
            
            return response()->json([
                'data' => [
                    'coupon' => new CouponResource($coupon),
                    'order_amount' => $request->order_amount,
                    'discount_value' => $discountValue,
                    'final_amount' => $request->order_amount - $discountValue
                ],
                'message' => 'Discount calculated successfully'
            ]);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}