<?php

namespace Modules\Cart\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Modules\Cart\Models\Cart;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Cart\Services\CartService;
use Modules\Address\Services\AddressService;
use Modules\Cart\Http\Resources\CartResource;
use Modules\Cart\Http\Requests\CreateCartRequest;
use Modules\Cart\Http\Requests\UpdateCartRequest;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Cart\Http\Requests\RemoveParticipantRequest;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(protected CartService $cartService, protected AddressService $addressService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $carts = $this->cartService->getCart();
        if (!$carts) {
            return $this->successResponse(null, __("message.success"));
        }
        return $this->successResponse([
            "carts" => CartResource::collection($carts),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCartRequest $request): JsonResponse
    {
        try {
            $cart = $this->cartService->createCart($request->all());
            return $this->successResponse([
                "cart" => new CartResource($cart->load(
                    'items.product',
                    'items.addOns',
                    'items.addedBy',
                    'store',
                    'user',
                    'participants',
                    'posShift',
                )),
            ], __("message.success"));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Show the specified resource.
     */
    public function show(string $key): JsonResponse
    {
        $cart = $this->cartService->getCartByCartKey($key);
        if (!$cart) {
            return $this->errorResponse(__("message.not_found"), 404);
        }
        $addressId = request()->header('address-id') ?? request()->header('Address-Id');
        $address = null;
        $statusMessage = null;
        if ($addressId) {
            $address = $this->addressService->getAddressById($addressId);
            $canDeliver = $cart->store->canDeliverTo($addressId);

            $isOpen = $cart->store->isOpenNow();
            $productsAvailable = $cart->items->every(function ($item) {
                return $item->product && $item->product->status === \App\Enums\ProductStatusEnum::ACTIVE;
            });

            $productsInStock = $cart->items->every(function ($item) {
                return $item->product && $item->product->stock >= $item->quantity;
            });

            if (!$canDeliver) {
                $statusMessage = __("message.store_cannot_deliver_to_address");
            } elseif (!$isOpen) {
                $statusMessage = __("message.store_is_closed");
            } elseif (!$productsAvailable) {
                $statusMessage = __("message.some_products_unavailable");
            } elseif (!$productsInStock) {
                $statusMessage = __("message.some_products_out_of_stock");
            }
        }
        $isDeliveryToThisArea = $this->cartService->isDeliveryToThisArea($cart, $address);
        return $this->successResponse([
            "cart" => new CartResource($cart->load(
                'items.product.images',
                'items.product.offers',
                'items.addOns',
                'items.addedBy',
                'store',
                'user',
                'participants',
                'posShift'
            )),
            // "address" => $address ? new AddressResource($address) : null,
            "status_message" => $statusMessage,
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartRequest $request, string $key): JsonResponse
    {
        try {
            $cart = $this->cartService->updateCart($key, $request->all());
            if (!$cart) {
                return $this->errorResponse(null, __("message.not_found"), 404);
            }
            return $this->successResponse([
                "cart" => new CartResource($cart->load(
                    'items.product',
                    'items.addOns',
                    'items.addedBy',
                    'store',
                    'user',
                    'participants',
                    'posShift'
                )),
            ], __("message.success"));
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->cartService->deleteCart($id);
        return $this->successResponse(null, __("message.success"));
    }
    public function shareCart(int $id): JsonResponse
    {
        $share = $this->cartService->getShareById($id);
        return $this->successResponse([
            "share" => $share,
        ], __("message.success"));
    }
    public function joinCart(Request $request): JsonResponse
    {
        $cartKey = $request->route('cart_key');
        if (!$cartKey) {
            return $this->errorResponse(null, __("message.invalid_request"), 400);
        }

        $cart = $this->cartService->joinCart($cartKey);
        if (!$cart) {
            return $this->errorResponse(null, __("message.not_found"), 404);
        }
        return $this->successResponse([
            "cart" => new CartResource($cart),
        ], __("message.success"));
    }

    public function removeParticipant(RemoveParticipantRequest $request): JsonResponse
    {
        $cartKey = $request->route('cart_key');
        if (!$cartKey) {
            return $this->errorResponse(null, __("message.invalid_request"), 400);
        }

        try {
            $cart = $this->cartService->removeParticipant($cartKey, $request->user_id);
            if (!$cart) {
                return $this->errorResponse(null, __("message.not_found"), 404);
            }
            return $this->successResponse([
                "cart" => new CartResource($cart),
            ], __("message.success"));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse(null, $e->getMessage(), 403);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
