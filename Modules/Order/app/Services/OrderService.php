<?php

namespace Modules\Order\Services;

use App\Traits\FileUploadTrait;
use Modules\Order\Models\Order;
use Modules\Coupon\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductOptionValue;
use Modules\Order\Repositories\OrderRepository;

class OrderService
{
    use FileUploadTrait;
    private $totalPrice = 0;
    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function getAllOrders()
    {
        return $this->orderRepository->paginate([], 15, ['items', 'deliveryAddress']);
    }

    public function getOrderById(int $id): ?Order
    {
        return $this->orderRepository->find($id);
    }

    public function createOrder(array $data): ?Order
    {
        return DB::transaction(function () use ($data) {
            $orderData = $data['order'] ?? [];
            $orderItems = $data['items'] ?? [];

            $orderData['order_number'] = $this->generateOrderNumber();
            $orderData['reference_code'] = $this->generateReferenceCode();

            $totalAmount = $this->calculateTotalAmount($orderItems);

            $discountAmount = 0.0;
            $coupon = null;
            if (!empty($orderData['coupon_code'])) {
                $coupon = Coupon::where('code', $orderData['coupon_code'])->first();
                if ($coupon && $coupon->isValid()) {
                    // إذا لديك شرط minimum_order_amount أو غيره
                    if ($totalAmount >= ($coupon->minimum_order_amount ?? 0)) {
                        $discountAmount = $coupon->getDiscountValue($totalAmount);
                        $orderData['coupon_id'] = $coupon->id;
                    }
                }
            }


        $orderData['total_amount'] = $totalAmount;
        $orderData['discount_amount'] = round($discountAmount, 2);


            $orderData['service_fee'] = $this->calculateServiceFee($orderData, $orderItems);
            $orderData['delivery_fee'] = $this->calculateDeliveryFee($orderData, $orderItems);
            $orderData['tax_amount'] = $this->calculateTaxAmount($orderData, $orderItems);
            $orderData['otp_code'] = ($orderData['requires_otp'] ?? false) ? rand(1000, 9999) : null;
            $orderData['estimated_delivery_time'] = $this->calculateEstimatedDeliveryTime($orderData);

            $orderData['user_id'] = auth('user')->id() ?? null;
            $orderData['store_id'] = $this->getStoreIdFromContext();

            $order = $this->orderRepository->create($orderData);

            $this->createOrderItems($order, $orderItems);

            return $order->refresh();
        });
    }


    private function createOrderItems(Order $order, array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $quantity = $item['quantity'] ?? 1;

            $product = Product::find($item['product_id']);
            $optionValue = isset($item['product_option_value_id'])
                ? ProductOptionValue::find($item['product_option_value_id'])
                : null;

            $productPrice = $product?->price?->price ?? 0;
            $optionPrice = $optionValue?->price ?? 0;

            $addOns = $item['add_ons'] ?? [];
            $addOnTotal = collect($addOns)
                ->sum(fn($ao) => ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1));

            $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

            $orderItem = $order->orderItems()->create([
                'product_id' => $product->id,
                'product_option_value_id' => $optionValue?->id,
                'quantity' => $quantity,
                'total_price' => $totalPrice,
            ]);

            if (!empty($addOns)) {
                $pivotData = collect($addOns)
                    ->mapWithKeys(fn($ao) => [
                        $ao['id'] => [
                            'quantity' => $ao['quantity'] ?? 1,
                            'price_modifier' => ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1),
                        ]
                    ])->toArray();

                $orderItem->addOns()->sync($pivotData);
            }
        }
    }

    private function getStoreIdFromContext(): ?int
    {
        return 1;
    }

    private function calculateEstimatedDeliveryTime(array $orderData): ?string
    {
        return now()->addMinutes(45)->toDateTimeString();
    }

    private function calculateServiceFee(array $orderData, array $items): float
    {
        $total = $orderData['total_amount'] ?? 0;
        return round($total * 0.05, 2);
    }

    private function calculateTaxAmount(array $orderData, array $items): float
    {
        $base = $orderData['total_amount']
            - ($orderData['discount_amount'] ?? 0)
            + ($orderData['service_fee'] ?? 0)
            + ($orderData['delivery_fee'] ?? 0);

        return round($base * 0.15, 2);
    }

    private function calculateDeliveryFee(array $orderData, array $items): float
    {
        return 10.00;
    }

    private function calculateTotalAmount(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;

            $product = Product::find($item['product_id']);
            $option = isset($item['product_option_value_id'])
                ? ProductOptionValue::find($item['product_option_value_id'])
                : null;

            $productPrice = $product?->price?->price ?? 0;
            $optionPrice = $option?->price ?? 0;

            $addOnTotal = collect($item['add_ons'] ?? [])
                ->sum(fn($ao) => ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1));

            $itemTotal = ($productPrice + $optionPrice + $addOnTotal) * $quantity;
            $total += $itemTotal;
        }

        return round($total, 2);
    }


    private function generateOrderNumber(): string
    {
        $lastOrder = $this->orderRepository->getLastOrder();
        $lastNum = $lastOrder ? (int)substr($lastOrder->order_number, 3) : 0;
        $newNum = str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        return 'ORD' . $newNum;
    }

    private function generateReferenceCode(): string
    {
        return strtoupper(uniqid('REF'));
    }

    public function updateOrder(int $id, array $data): ?Order
    {
        return DB::transaction(function () use ($id, $data) {
            $filtered = array_filter($data, fn($v) => !blank($v));
            return $this->orderRepository->update($id, $filtered);
        });
    }

    public function deleteOrder(int $id): bool
    {
        return $this->orderRepository->delete($id);
    }
}
