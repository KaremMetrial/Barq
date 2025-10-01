<?php

namespace Modules\Cart\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Cart\Services\CartService;
use Modules\Cart\Http\Resources\CartResource;
use Modules\Cart\Http\Requests\CreateCartRequest;
use Modules\Cart\Http\Requests\UpdateCartRequest;

class CartController extends Controller
{
    use ApiResponse;

    public function __construct(protected CartService $cartService)
    {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $carts = $this->cartService->getAllCarts();
        return $this->successResponse([
            "carts" => CartResource::collection($carts),
        ], __("message.success"));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateCartRequest $request): JsonResponse
    {
        $cart = $this->cartService->createCart($request->all());
        return $this->successResponse([
            "cart" => new CartResource($cart),
        ], __("message.success"));
    }

    /**
     * Show the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $cart = $this->cartService->getCartById($id);
        return $this->successResponse([
            "cart" => new CartResource($cart),
        ], __("message.success"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCartRequest $request, int $id): JsonResponse
    {
        $cart = $this->cartService->updateCart($id, $request->all());
        return $this->successResponse([
            "cart" => new CartResource($cart),
        ], __("message.success"));
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
        $cart = $this->cartService->joinCart($request->all());
        return $this->successResponse([
            "cart" => new CartResource($cart),
        ], __("message.success"));
    }
}
