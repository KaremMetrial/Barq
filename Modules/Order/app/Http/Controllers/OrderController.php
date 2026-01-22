<?php

namespace Modules\Order\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Modules\Order\Services\OrderService;
use App\Http\Resources\PaginationResource;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Http\Requests\CreateOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderRequest;
use Modules\Order\Http\Requests\UpdateOrderStatusRequest;
use Modules\Order\Http\Resources\OrderCollectionResource;
use App\Enums\OrderStatus;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(protected OrderService $orderService) {}

    /**
     * Display a listing of the orders.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = auth('user')->id();
        $filter = $request->only('search', 'status', 'from_date', 'to_date', 'courier_id');

        // Get current order (latest active order)
        $currentOrder = $this->orderService->getCurrentOrder($userId);

        // Get finished orders (delivered or cancelled)
        $finishedOrders = $this->orderService->getFinishedOrders($userId, $filter);

        return $this->successResponse([
            'current_orders' => $currentOrder ? OrderResource::collection($currentOrder) : null,
            'finished_orders' => OrderResource::collection($finishedOrders),
            'pagination' => new PaginationResource($finishedOrders),
        ], __('message.success'));
    }
    public function courierIndex(Request $request): JsonResponse
    {
        $userId = auth('courier')->id();
        $filter = $request->only('search', 'status', 'from_date', 'to_date', 'courier_id');

        $finishedOrders = $this->orderService->getCourierOrders($userId, $filter);

        return $this->successResponse([
            'orders' => OrderResource::collection($finishedOrders),
        ], __('message.success'));
    }

    /**
     * Store a newly created order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->createOrder($request());
        return $this->successResponse([
            'order' => new OrderResource($order),
        ], __('message.success'));
    }

    /**
     * Display the specified order.
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        return $this->successResponse([
            'order' => new OrderResource($order->load('reviews')),
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
     * Update the status of the specified order.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, int $id): JsonResponse
    {
        $order = $this->orderService->updateOrderStatus($id, $request->validated());

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
