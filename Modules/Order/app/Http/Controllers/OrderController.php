<?php

namespace Modules\Order\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Order\Services\OrderService;
use App\Http\Resources\PaginationResource;
use Illuminate\Http\Request;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Http\Requests\CreateOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderRequest;
use Modules\Order\Http\Resources\OrderCollectionResource;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->only('search', 'status');
        $orders = $this->orderService->getAllOrders($filter);

        return $this->successResponse([
            'orders' => OrderResource::collection($orders),
            'pagination' => new PaginationResource($orders),
        ], __('message.success'));
    }

    /**
     * Store a newly created order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->validated());
        // dd($order->load('items.product', 'items.addOns', 'store', 'user', 'courier', 'statusHistories'));
        return $this->successResponse([
            'order' => new OrderResource($order->load('items', 'items.product', 'items.addOns', 'store', 'user', 'courier', 'statusHistories', 'deliveryAddress')),
        ], __('message.success'));
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        $order->load('items', 'items.product', 'items.addOns', 'store', 'user', 'courier', 'statusHistories', 'deliveryAddress');
        return $this->successResponse([
            'order' => new OrderResource($order),
        ], __('message.success'));
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->updateOrder($id, $request->all());

        return $this->successResponse([
            'order' => new OrderResource($order),
        ], __('message.success'));
    }

    /**
     * Remove the specified order from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->orderService->deleteOrder($id);

        return $this->successResponse(null, __('message.success'));
    }
}
