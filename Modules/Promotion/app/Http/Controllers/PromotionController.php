<?php

namespace Modules\Promotion\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Promotion\Services\PromotionEngineService;
use Modules\Promotion\Services\PromotionService;
use Modules\Promotion\Http\Resources\PromotionResource;
use Modules\Promotion\Http\Requests\CreatePromotionRequest;
use Modules\Promotion\Http\Requests\UpdatePromotionRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Traits\ApiResponse;

class PromotionController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected PromotionEngineService $promotionEngineService,
        protected PromotionService $promotionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $promotions = $this->promotionService->getAllPromotions($filters);

        return $this->successResponse([
            'promotions' => PromotionResource::collection($promotions),
        ], __('message.success'));
    }

    public function store(CreatePromotionRequest $request): JsonResponse
    {
        $promotion = $this->promotionService->createPromotion($request->all());

        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function show(int $id): JsonResponse
    {
        $promotion = $this->promotionService->getPromotionById($id);

        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function update(UpdatePromotionRequest $request, int $id): JsonResponse
    {
        $promotion = $this->promotionService->updatePromotion($id, $request->all());

        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
        ], __('message.success'));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->promotionService->deletePromotion($id);

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

        $promotion = $this->promotionService->getPromotionById($code);

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

        $promotion = $this->promotionService->getPromotionById($code);

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

        $this->applyPromotionToCart($cart, $appliedPromotion);

        return $this->successResponse([
            'promotion' => new PromotionResource($promotion),
            'new_order_total' => $result['new_order_total'],
            'savings' => $appliedPromotion['savings'],
        ], __('message.promotion_applied'));
    }

    private function getCartFromId(string $cartId)
    {
        return \Modules\Cart\Models\Cart::find($cartId);
    }

    private function applyPromotionToCart($cart, array $promotion): void
    {
        if ($promotion['type'] === 'delivery') {
            $cart->update(['delivery_cost' => $promotion['new_delivery_cost']]);
        }

        if ($promotion['type'] === 'product') {
            // In a manual apply, we could mark items as discounted or adjust their total_price
            // However, since we now have automatic evaluation in Resources,
            // we'll just ensure the cart metadata records the applied promotion.
            $cart->update([
                'applied_promotions' => array_unique(array_merge($cart->applied_promotions ?? [], [$promotion['promotion']['id']]))
            ]);
        }

        $cart->save();
    }
}
