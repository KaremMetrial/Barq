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

class AdminOrderController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    /**
     * Display a listing of all orders (admin sees all).
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->only('search','status','from_date','to_date');
        $orders = $this->orderService->getAllOrders($filter);

        return $this->successResponse([
            'orders' => OrderResource::collection($orders->load('user', 'courier','items.product', 'paymentMethod')),
            'pagination' => new PaginationResource($orders),
        ], __('message.success'));
    }

    /**
     * Store a newly created order (admin can create orders).
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request->all());
        return $this->successResponse([
            'order' => new OrderResource($order)
        ], __('message.success'));
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        // $order->load('items.product', 'items.productOptionValue', 'items.addOns', 'store', 'user', 'courier', 'statusHistories');

        return $this->successResponse([
            'order' => new OrderResource($order->load('statusHistories', 'paymentMethod')),
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
     * Delete the specified order.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->orderService->deleteOrder($id);

        return $this->successResponse(null, __('message.success'));
    }
    public function stats(): JsonResponse
    {
        $stats = $this->orderService->getStats();

        return $this->successResponse($stats, __('message.success'));
    }
}
