<?php

namespace Modules\Order\Services;

use App\Enums\StoreStatusEnum;
use Modules\Zone\Models\Zone;
use App\Traits\FileUploadTrait;
use Modules\Order\Models\Order;
use Modules\Store\Models\Store;
use Modules\Coupon\Models\Coupon;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Cache;
use Modules\StoreSetting\Models\StoreSetting;
use Modules\Product\Models\ProductOptionValue;
use Modules\Order\Repositories\OrderRepository;

class OrderService
{
    use FileUploadTrait;

    private float $totalPrice = 0;
    private ?Store $currentStore = null;
    private ?StoreSetting $storeSettings = null;

    public function __construct(
        protected OrderRepository $orderRepository
    ) {}

    public function getAllOrders(array $filter = [])
    {
        return $this->orderRepository->paginate($filter, 15, ['items', 'deliveryAddress']);
    }

    public function getOrderById(int $id): ?Order
    {
        return $this->orderRepository->find($id, [
            'orderItems.product.images',
            'orderItems.productOptionValue',
            'orderItems.addOns',
            'store',
            'user'
        ]);
    }

    public function createOrder(array $data): ?Order
    {
        return DB::transaction(function () use ($data) {
            $orderData = $data['order'] ?? [];
            $orderItems = $data['items'] ?? [];

            // Validate store exists and is operational
            $this->validateStoreAvailability($orderData['store_id'] ?? null);

            $orderData['order_number'] = $this->generateOrderNumber();
            $orderData['reference_code'] = $this->generateReferenceCode();

            // Calculate totals
            $totalAmount = $this->calculateTotalAmount($orderItems);

            // Apply coupon if provided
            $discountAmount = 0.0;
            $coupon = null;

            if (!empty($orderData['coupon_code'])) {
                $couponResult = $this->applyCoupon(
                    $orderData['coupon_code'],
                    $totalAmount,
                    $orderData['store_id'] ?? null,
                    $orderItems
                );

                if ($couponResult['valid']) {
                    $discountAmount = $couponResult['discount'];
                    $orderData['coupon_id'] = $couponResult['coupon']->id;
                    $coupon = $couponResult['coupon'];
                }
            }

            $orderData['total_amount'] = $totalAmount;
            $orderData['discount_amount'] = round($discountAmount, 3);

            // Calculate fees based on store settings
            $orderData['delivery_fee'] = $this->calculateDeliveryFee($orderData);
            $orderData['service_fee'] = $this->calculateServiceFee($orderData);
            $orderData['tax_amount'] = $this->calculateTaxAmount($orderData);

            // OTP for delivery
            $orderData['otp_code'] = ($orderData['requires_otp'] ?? false)
                ? str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT)
                : null;

            // Estimated delivery time
            $orderData['estimated_delivery_time'] = $this->calculateEstimatedDeliveryTime();

            // Set user and store
            $orderData['user_id'] = auth('user')->id() ?? $orderData['user_id'] ?? null;
            $orderData['store_id'] = $this->currentStore->id;

            // Validate minimum order amount
            if ($this->storeSettings && $this->storeSettings->minimum_order_amount) {
                if ($totalAmount < $this->storeSettings->minimum_order_amount) {
                    throw new \Exception(
                        "Minimum order amount is {$this->storeSettings->minimum_order_amount}"
                    );
                }
            }

            // Create order
            $order = $this->orderRepository->create($orderData);

            // Create order items with add-ons
            $this->createOrderItems($order, $orderItems);

            // Update coupon usage if applicable
            if ($coupon) {
                $this->incrementCouponUsage($coupon, $orderData['user_id']);
            }

            // Decrease product stock
            $this->decreaseProductStock($orderItems);

            return $order->refresh();
        });
    }

    /**
     * Validate store availability
     */
    private function validateStoreAvailability(?int $storeId): void
    {
        if (!$storeId) {
            throw new \Exception('Store ID is required');
        }

        $this->currentStore = Store::with('StoreSetting')->find($storeId);
        if (!$this->currentStore) {
            throw new \Exception('Store not found');
        }

        if (!$this->currentStore->is_active || $this->currentStore->status != StoreStatusEnum::APPROVED) {
            throw new \Exception('Store is not available');
        }
        if ($this->currentStore->is_closed) {
            throw new \Exception('Store is currently closed');
        }

        $this->storeSettings = $this->currentStore->settings;

        if ($this->storeSettings && !$this->storeSettings->orders_enabled) {
            throw new \Exception('Store is not accepting orders');
        }
        // Check working hours
        if (!$this->isStoreOpen()) {
            throw new \Exception('Store is closed at this time');
        }
    }

    /**
     * Check if store is open based on working days
     */
    private function isStoreOpen(): bool
    {
        $currentDay = now()->dayOfWeek;
        $currentTime = now()->format('H:i:s');
        $workingDay = $this->currentStore->workingDays()
            ->where('day_of_week', $currentDay)
            ->first();
        if (!$workingDay) {
            return false;
        }
        return $currentTime >= $workingDay->open_time
            && $currentTime <= $workingDay->close_time;
    }

    /**
     * Apply and validate coupon
     */
    private function applyCoupon(
        string $code,
        float $totalAmount,
        ?int $storeId,
        array $orderItems
    ): array {
        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Invalid coupon code'];
        }

        // Check date validity
        if ($coupon->start_date && now()->lt($coupon->start_date)) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Coupon not yet valid'];
        }

        if ($coupon->end_date && now()->gt($coupon->end_date)) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Coupon expired'];
        }

        // Check minimum order amount
        if ($totalAmount < $coupon->minimum_order_amount) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => "Minimum order amount is {$coupon->minimum_order_amount}"
            ];
        }

        // Check usage limits
        if ($coupon->usage_limit && $coupon->usageCount() >= $coupon->usage_limit) {
            return ['valid' => false, 'discount' => 0, 'message' => 'Coupon usage limit reached'];
        }

        // Check per-user usage limit
        $userId = auth('user')->id();
        if ($userId && $coupon->getUserUsageCount($userId) >= $coupon->usage_limit_per_user) {
            return ['valid' => false, 'discount' => 0, 'message' => 'You have reached the usage limit for this coupon'];
        }

        // Check object type (store/product/category specific)
        if ($coupon->object_type !== 'general') {
            $isApplicable = $this->isCouponApplicable($coupon, $storeId, $orderItems);
            if (!$isApplicable) {
                return ['valid' => false, 'discount' => 0, 'message' => 'Coupon not applicable to this order'];
            }
        }

        // Calculate discount
        $discount = $this->calculateCouponDiscount($coupon, $totalAmount);

        return [
            'valid' => true,
            'discount' => $discount,
            'coupon' => $coupon,
            'message' => 'Coupon applied successfully'
        ];
    }

    /**
     * Check if coupon is applicable to order
     */
    private function isCouponApplicable(Coupon $coupon, ?int $storeId, array $orderItems): bool
    {
        switch ($coupon->object_type) {
            case 'store':
                return $coupon->stores()->where('store_id', $storeId)->exists();

            case 'product':
                $productIds = collect($orderItems)->pluck('product_id')->toArray();
                return $coupon->products()->whereIn('product_id', $productIds)->exists();

            case 'category':
                $products = Product::whereIn('id', collect($orderItems)->pluck('product_id'))
                    ->get();
                $categoryIds = $products->pluck('category_id')->unique()->toArray();
                return $coupon->categories()->whereIn('category_id', $categoryIds)->exists();

            default:
                return true;
        }
    }

    /**
     * Calculate discount based on coupon type
     */
    private function calculateCouponDiscount(Coupon $coupon, float $totalAmount): float
    {
        if ($coupon->discount_type === 'percentage') {
            $discount = ($totalAmount * $coupon->discount_amount) / 100;
        } else {
            $discount = $coupon->discount_amount;
        }

        // Don't exceed total amount
        return min($discount, $totalAmount);
    }

    /**
     * Increment coupon usage
     */
    private function incrementCouponUsage(Coupon $coupon, ?int $userId): void
    {
        // This would typically be in a CouponUsage table
        // For now, you might track in a separate table or cache
        if ($userId) {
            Cache::increment("coupon_{$coupon->id}_user_{$userId}_usage");
        }
    }

    /**
     * Create order items with add-ons
     */
    private function createOrderItems(Order $order, array $orderItems): void
    {
        $productIds = collect($orderItems)->pluck('product_id')->unique();
        $optionIds = collect($orderItems)->pluck('product_option_value_id')->filter()->unique();

        $products = Product::with(['price', 'availability'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $options = ProductOptionValue::whereIn('id', $optionIds)
            ->get()
            ->keyBy('id');

        foreach ($orderItems as $item) {
            $quantity = $item['quantity'] ?? 1;
            $product = $products[$item['product_id']] ?? null;

            if (!$product) {
                continue;
            }

            // Validate stock
            if (!$product->availability?->is_in_stock) {
                throw new \Exception("Product {$product->id} is out of stock");
            }

            if ($product->availability && $product->availability->stock_quantity < $quantity) {
                throw new \Exception("Insufficient stock for product {$product->id}");
            }

            // Check max cart quantity
            if ($quantity > $product->max_cart_quantity) {
                throw new \Exception("Maximum quantity for product {$product->id} is {$product->max_cart_quantity}");
            }

            $option = isset($item['product_option_value_id'])
                ? ($options[$item['product_option_value_id']] ?? null)
                : null;

            $productPrice = $product->price?->price ?? 0;
            $optionPrice = $option?->price ?? 0;
            $addOns = $item['add_ons'] ?? [];

            $addOnTotal = collect($addOns)->sum(function ($ao) {
                return ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1);
            });

            $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

            $orderItem = $order->orderItems()->create([
                'product_id' => $product->id,
                'product_option_value_id' => $option?->id,
                'quantity' => $quantity,
                'total_price' => round($totalPrice, 3),
            ]);

            // Attach add-ons if any
            if (!empty($addOns)) {
                $pivotData = collect($addOns)->mapWithKeys(function ($ao) {
                    return [
                        $ao['id'] => [
                            'quantity' => $ao['quantity'] ?? 1,
                            'price_modifier' => round(($ao['price'] ?? 0) * ($ao['quantity'] ?? 1), 3),
                        ]
                    ];
                })->toArray();

                $orderItem->addOns()->sync($pivotData);
            }
        }
    }

    /**
     * Decrease product stock after order
     */
    private function decreaseProductStock(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $product = Product::find($item['product_id']);
            if ($product && $product->availability) {
                $product->availability->decrement('stock_quantity', $item['quantity'] ?? 1);

                // Mark as out of stock if needed
                if ($product->availability->stock_quantity <= 0) {
                    $product->availability->update(['is_in_stock' => false]);
                }
            }
        }
    }

    /**
     * Calculate delivery fee based on zone and settings
     */
    private function calculateDeliveryFee(array $orderData): float
    {
        // If free delivery is enabled
        if ($this->storeSettings?->free_delivery_enabled) {
            return 0.0;
        }

        // If delivery is disabled
        if (!$this->storeSettings?->delivery_service_enabled) {
            return 0.0;
        }

        // Get delivery zone if address provided
        if (isset($orderData['delivery_address'])) {
            // Here you would calculate based on zone
            // For now, return a default
            return 10.00;
        }

        return 10.00;
    }

    /**
     * Calculate service fee from store settings
     */
    private function calculateServiceFee(array $orderData): float
    {
        if (!$this->storeSettings) {
            return 0.0;
        }

        $subtotal = $orderData['total_amount'] - ($orderData['discount_amount'] ?? 0);
        $feePercentage = $this->storeSettings->service_fee_percentage ?? 0;

        return round($subtotal * ($feePercentage / 100), 3);
    }

    /**
     * Calculate tax from store settings
     */
    private function calculateTaxAmount(array $orderData): float
    {
        if (!$this->storeSettings || !$this->storeSettings->tax_rate) {
            return 0.0;
        }

        $taxableAmount = $orderData['total_amount']
            - ($orderData['discount_amount'] ?? 0)
            + ($orderData['service_fee'] ?? 0)
            + ($orderData['delivery_fee'] ?? 0);

        return round($taxableAmount * ($this->storeSettings->tax_rate / 100), 3);
    }

    /**
     * Calculate total amount from items
     */
    private function calculateTotalAmount(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;

            $product = Product::with('price')->find($item['product_id']);
            $option = isset($item['product_option_value_id'])
                ? ProductOptionValue::find($item['product_option_value_id'])
                : null;

            $productPrice = $product?->price?->price ?? 0;
            $optionPrice = $option?->price ?? 0;

            $addOnTotal = collect($item['add_ons'] ?? [])->sum(function ($ao) {
                return ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1);
            });

            $itemTotal = ($productPrice + $optionPrice + $addOnTotal) * $quantity;
            $total += $itemTotal;
        }

        return round($total, 3);
    }

    /**
     * Calculate estimated delivery time
     */
    private function calculateEstimatedDeliveryTime(): string
    {
        $minTime = $this->storeSettings?->delivery_time_min ?? 30;
        $maxTime = $this->storeSettings?->delivery_time_max ?? 45;
        $unit = $this->storeSettings?->delivery_type_unit ?? 'minute';

        $estimatedTime = ($minTime + $maxTime) / 2;

        return match ($unit) {
            'hour' => now()->addHours($estimatedTime)->toDateTimeString(),
            'day' => now()->addDays($estimatedTime)->toDateTimeString(),
            default => now()->addMinutes($estimatedTime)->toDateTimeString(),
        };
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $lastOrder = $this->orderRepository->getLastOrder();
        $lastNum = $lastOrder ? (int)substr($lastOrder->order_number, 3) : 0;
        $newNum = str_pad($lastNum + 1, 6, '0', STR_PAD_LEFT);
        return 'ORD' . $newNum;
    }

    /**
     * Generate reference code
     */
    private function generateReferenceCode(): string
    {
        return strtoupper(uniqid('REF'));
    }

    public function updateOrder(int $id, array $data): ?Order
    {
        return DB::transaction(function () use ($id, $data) {
            $order = $this->orderRepository->find($id);

            if (!$order) {
                throw new \Exception('Order not found');
            }

            // Prevent updating completed/cancelled orders
            if (in_array($order->status, ['completed', 'cancelled'])) {
                throw new \Exception('Cannot update completed or cancelled orders');
            }

            $filtered = array_filter($data, fn($v) => !blank($v));
            return $this->orderRepository->update($id, $filtered);
        });
    }

    public function deleteOrder(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $order = $this->orderRepository->find($id);

            if (!$order) {
                return false;
            }

            // Only allow deletion of pending orders
            if ($order->status !== 'pending') {
                throw new \Exception('Can only delete pending orders');
            }

            // Restore stock
            foreach ($order->orderItems as $item) {
                if ($item->product && $item->product->availability) {
                    $item->product->availability->increment('stock_quantity', $item->quantity);
                    $item->product->availability->update(['is_in_stock' => true]);
                }
            }

            return $this->orderRepository->delete($id);
        });
    }
}
