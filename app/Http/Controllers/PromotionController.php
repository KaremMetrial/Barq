<?php

namespace App\Http\Controllers;

use App\Services\PromotionEngineService;
use App\Services\CouponService;
use App\Http\Resources\PromotionResource;
use App\Http\Requests\CreatePromotionRequest;
use App\Http\Requests\UpdatePromotionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

class PromotionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PromotionEngineService $promotionEngineService,
        protected CouponService $couponService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $promotions = $this->couponService->getAllCoupons($filters);
        
        return $this->successResponse([
            'promotions' => PromotionResource::collection($promotions),
        ], __('message.success'));
    }

    public function store(CreatePromotionRequest $request): JsonResponse
    {
        $promotion = $this->couponService->createCoupon($request->all());
        
        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function show(int $id): JsonResponse
    {
        $promotion = $this->couponService->getCouponById($id);
        
        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function update(UpdatePromotionRequest $request, int $id): JsonResponse
    {
        $promotion = $this->couponService->updateCoupon($id, $request->all());
        
        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->couponService->deleteCoupon($id);
        
        return $this->successResponse(null, __('message.success'));
    }

    public function available(Request $request): JsonResponse
    {
        $cartId = $request->header('X-Cart-ID');
        $cart = $this->getCartFromId($cartId);
        
        if (!$cart) {
            return $this->errorResponse(__('message.cart_not_found'), 404);
        }

        $store = $cart->store;
        $user = auth('api')->user();
        
        $result = $this->promotionEngineService->evaluatePromotions($cart, $store, $user);
        
        return $this->successResponse([
            'available_promotions' => $result['promotions'],
            'total_savings' => $result['total_savings'],
            'new_order_total' => $result['new_order_total'],
        ], __('message.success'));
    }

    public function validate(Request $request, string $code): JsonResponse
    {
        $cartId = $request->header('X-Cart-ID');
        $cart = $this->getCartFromId($cartId);
        
        if (!$cart) {
            return $this->errorResponse(__('message.cart_not_found'), 404);
        }

        $promotion = $this->couponService->getCouponById($code);
        
        if (!$promotion) {
            return $this->errorResponse(__('message.promotion_not_found'), 404);
        }

        $store = $cart->store;
        $user = auth('api')->user();
        
        $result = $this->promotionEngineService->evaluatePromotions($cart, $store, $user);
        
        $isValid = collect($result['promotions'])->contains('promotion.id', $promotion->id);
        
        return $this->successResponse([
            'is_valid' => $isValid,
            'promotion' => $isValid ? new PromotionResource($promotion) : null,
        ], __('message.success'));
    }

    public function apply(Request $request, string $code): JsonResponse
    {
        $cartId = $request->header('X-Cart-ID');
        $cart = $this->getCartFromId($cartId);
        
        if (!$cart) {
            return $this->errorResponse(__('message.cart_not_found'), 404);
        }

        $promotion = $this->couponService->getCouponById($code);
        
        if (!$promotion) {
            return $this->errorResponse(__('message.promotion_not_found'), 404);
        }

        $store = $cart->store;
        $user = auth('api')->user();
        
        $result = $this->promotionEngineService->evaluatePromotions($cart, $store, $user);
        
        $appliedPromotion = collect($result['promotions'])->firstWhere('promotion.id', $promotion->id);
        
        if (!$appliedPromotion) {
            return $this->errorResponse(__('message.promotion_not_applicable'), 422);
        }

        // تطبيق الترويج على السلة
        $this->applyPromotionToCart($cart, $appliedPromotion);
        
        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
            'new_order_total' => $result['new_order_total'],
            'savings' => $appliedPromotion['savings'],
        ], __('message.promotion_applied'));
    }

    private function getCartFromId(string $cartId)
    {
        // تنفيذ منطق الحصول على السلة من المعرف
        return \Modules\Cart\Models\Cart::find($cartId);
    }

    private function applyPromotionToCart($cart, array $promotion): void
    {
        // تنفيذ منطق تطبيق الترويج على السلة
        if ($promotion['type'] === 'delivery') {
            $cart->update(['delivery_cost' => $promotion['new_delivery_cost']]);
        }
        
        if ($promotion['type'] === 'product') {
            // تحديث أسعار المنتجات في السلة
            foreach ($cart->items as $item) {
                // تحديث السعر إذا كان منتجًا مؤهلاً
            }
        }
        
        $cart->save();
    }
}