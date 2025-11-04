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
use Modules\Address\Models\Address;
use App\Models\ShippingPrice;

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
            'orderItems.addOns',
            'store',
            'user',
            'deliveryAddress'
        ]);
    }

    public function createOrder(array $data): ?Order
    {
        return DB::transaction(function () use ($data) {
            $orderData = $data['order'] ?? [];
            $orderItems = $data['items'] ?? [];

            // Find optimal branch for fulfilling the order
            $optimalBranch = $this->findOptimalBranch($orderData, $orderItems);
            $orderData['store_id'] = $optimalBranch->id;

            // Validate store exists and is operational
            $this->validateStoreAvailability($orderData['store_id']);

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
            $orderData['estimated_delivery_time'] = $this->calculateEstimatedDeliveryTime($orderData['delivery_address_id'] ?? null);

            // Set user and store (store_id is already set by findOptimalBranch)
            $orderData['user_id'] = auth('user')->id() ?? $orderData['user_id'] ?? null;

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

            // Decrease product stock (wrapped in transaction for safety)
            DB::transaction(function () use ($orderItems) {
                $this->decreaseProductStock($orderItems);
            });

            // Award loyalty points after successful order
            if ($order->user_id) {
                $loyaltyService = app(\Modules\User\Services\LoyaltyService::class);
                $loyaltyService->awardPoints(
                    $order->user_id,
                    $orderData['total_amount'],
                    "Points earned from order #{$order->order_number}",
                    $order
                );
            }

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
        // Increment global coupon usage count
        $coupon->increment('usage_count');

        // Track per-user usage if user is authenticated
        if ($userId) {
            Cache::increment("coupon_{$coupon->id}_user_{$userId}_usage");

            // Also store in database if you have a CouponUsage table
            // For now, using cache with a longer TTL for persistence
            $currentUsage = Cache::get("coupon_{$coupon->id}_user_{$userId}_usage", 0);
            Cache::put("coupon_{$coupon->id}_user_{$userId}_usage", $currentUsage, now()->addDays(30));
        }
    }

    /**
     * Create order items with add-ons
     */
    private function createOrderItems(Order $order, array $orderItems): void
    {
        $productIds = collect($orderItems)->pluck('product_id')->unique();
        $optionIds = collect($orderItems)->pluck('product_option_value_id')->filter()->flatten()->unique();

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

            $optionPrice = 0;

            // Always treat product_option_value_id as an array (JSON field)
            $optionIds = $item['product_option_value_id'] ?? [];
            if (!is_array($optionIds)) {
                $optionIds = $optionIds ? [$optionIds] : [];
            }

            foreach ($optionIds as $optionId) {
                $opt = $options[$optionId] ?? null;
                if ($opt) {
                    $optionPrice += (float) $opt->price;
                }
            }

            $productPrice = $product->price?->price ?? 0;
            $addOns = $item['add_ons'] ?? [];

            $addOnTotal = collect($addOns)->sum(function ($ao) {
                return ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1);
            });

            $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

            // Store option IDs as JSON array
            $orderItem = $order->orderItems()->create([
                'product_id' => $product->id,
                'product_option_value_id' => !empty($optionIds) ? $optionIds : null,
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
     * Decrease product stock after order from the specific branch
     */
    private function decreaseProductStock(array $orderItems): void
    {
        foreach ($orderItems as $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'] ?? 1;

            // Get availability for this product in the current store/branch
            $availability = \Modules\Product\Models\ProductAvailability::where('product_id', $productId)
                ->where('store_id', $this->currentStore->id)
                ->first();

            if (!$availability) {
                throw new \Exception("Product {$productId} is not available in this store branch");
            }

            // Check if sufficient stock is available before decrementing
            if (!$availability->is_in_stock || $availability->stock_quantity < $quantity) {
                throw new \Exception("Insufficient stock for product {$productId}. Available: {$availability->stock_quantity}, Requested: {$quantity}");
            }

            $availability->decrement('stock_quantity', $quantity);

            // Mark as out of stock if needed
            if ($availability->stock_quantity <= 0) {
                $availability->update(['is_in_stock' => false]);
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

        // Check if any shipping prices are configured in the system
        if (!ShippingPrice::exists()) {
            // If no shipping prices configured, use store default or throw error
            if ($this->storeSettings?->delivery_fee) {
                return round($this->storeSettings->delivery_fee, 3);
            }
            throw new \Exception('Delivery service is not configured for this store. Please contact store support.');
        }

        // Get delivery zone if address provided
        if (isset($orderData['delivery_address_id'])) {
            // Validate that store can deliver to this address
            $this->validateDeliveryAddress($orderData['delivery_address_id']);

            // Get delivery address to determine zone
            $deliveryAddress = Address::find($orderData['delivery_address_id']);
            if ($deliveryAddress && $deliveryAddress->zone_id) {
                // Calculate delivery fee based on zone and distance
                $zoneId = $deliveryAddress->zone_id;
                $shippingPrice = ShippingPrice::where('zone_id', $zoneId)->first();

                if ($shippingPrice) {
                    // Calculate distance between store and delivery address
                    $storeAddress = $this->currentStore->address;
                    if ($storeAddress && $deliveryAddress->latitude && $deliveryAddress->longitude) {
                        $distanceKm = $this->calculateDistance(
                            $storeAddress->latitude,
                            $storeAddress->longitude,
                            $deliveryAddress->latitude,
                            $deliveryAddress->longitude
                        );

                        $fee = $shippingPrice->base_price + ($shippingPrice->per_km_price * $distanceKm);
                        if ($shippingPrice->max_price && $fee > $shippingPrice->max_price) {
                            $fee = $shippingPrice->max_price;
                        }

                        return round($fee, 3);
                    }
                }
            }
        }

        // Fallback to store default delivery fee
        return round($this->storeSettings?->delivery_fee ?? 10.00, 3);
    }

    /**
     * Validate if store can deliver to the given address
     */
    private function validateDeliveryAddress(int $addressId): void
    {
        $deliveryAddress = Address::find($addressId);
        if (!$deliveryAddress) {
            throw new \Exception('Delivery address not found');
        }

        if (!$deliveryAddress->zone_id) {
            throw new \Exception('Delivery address does not have a valid zone');
        }

        // Check if store has any shipping prices configured
        $storeHasShippingPrices = ShippingPrice::exists();
        if (!$storeHasShippingPrices) {
            throw new \Exception('Store delivery service is not configured. Please contact store support.');
        }

        // Check if store has shipping prices for this specific zone
        $shippingPriceExists = ShippingPrice::where('zone_id', $deliveryAddress->zone_id)->exists();
        if (!$shippingPriceExists) {
            throw new \Exception('Store does not deliver to this address location');
        }

        // Ensure store has an address with coordinates for distance calculation
        $storeAddress = $this->currentStore->address;
        if (!$storeAddress || !$storeAddress->latitude || !$storeAddress->longitude) {
            throw new \Exception('Store address is not properly configured for delivery calculations');
        }

        // Ensure delivery address has coordinates
        if (!$deliveryAddress->latitude || !$deliveryAddress->longitude) {
            throw new \Exception('Delivery address coordinates are missing');
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Radius of the earth in km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
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
            $optionPrice = 0;

            // Always treat product_option_value_id as an array (JSON field)
            $optionIds = $item['product_option_value_id'] ?? [];
            if (!is_array($optionIds)) {
                $optionIds = $optionIds ? [$optionIds] : [];
            }

            foreach ($optionIds as $optionId) {
                $opt = ProductOptionValue::find($optionId);
                if ($opt) {
                    $optionPrice += (float) $opt->price;
                }
            }

            $productPrice = $product?->price?->price ?? 0;

            $addOnTotal = collect($item['add_ons'] ?? [])->sum(function ($ao) {
                return ($ao['price'] ?? 0) * ($ao['quantity'] ?? 1);
            });

            $itemTotal = ($productPrice + $optionPrice + $addOnTotal) * $quantity;
            $total += $itemTotal;
        }

        return round($total, 3);
    }

    /**
     * Calculate estimated delivery time dynamically based on distance and store load
     */
    private function calculateEstimatedDeliveryTime(?int $deliveryAddressId = null): string
    {
        $unit = $this->storeSettings?->delivery_type_unit ?? \App\Enums\DeliveryTypeUnitEnum::MINUTE;

        // Calculate distance in km (0 if no delivery address)
        $distanceKm = 0;
        if ($deliveryAddressId) {
            $deliveryAddress = Address::find($deliveryAddressId);
            if ($deliveryAddress && $deliveryAddress->latitude && $deliveryAddress->longitude) {
                $storeAddress = $this->currentStore->address;
                if ($storeAddress && $storeAddress->latitude && $storeAddress->longitude) {
                    $distanceKm = $this->calculateDistance(
                        $storeAddress->latitude,
                        $storeAddress->longitude,
                        $deliveryAddress->latitude,
                        $deliveryAddress->longitude
                    );
                }
            }
        }

        // Count pending orders for the store
        $pendingOrdersCount = Order::where('store_id', $this->currentStore->id)
            ->where('status', 'pending')
            ->count();

        // Calculate dynamic min and max in minutes
        $baseMinMinutes = 20;
        $baseMaxMinutes = 40;
        $distanceFactorMin = 2; // minutes per km for min
        $distanceFactorMax = 3; // minutes per km for max
        $loadFactorMin = 1; // minutes per pending order for min
        $loadFactorMax = 2; // minutes per pending order for max

        $dynamicMinMinutes = $baseMinMinutes + ($distanceKm * $distanceFactorMin) + ($pendingOrdersCount * $loadFactorMin);
        $dynamicMaxMinutes = $baseMaxMinutes + ($distanceKm * $distanceFactorMax) + ($pendingOrdersCount * $loadFactorMax);

        // Calculate estimated time as average in minutes
        $estimatedTimeMinutes = ($dynamicMinMinutes + $dynamicMaxMinutes) / 2;

        // Convert to the appropriate unit
        $estimatedTime = match ($unit->value) {
            'hour' => $estimatedTimeMinutes / 60,
            'day' => $estimatedTimeMinutes / (60 * 24),
            default => $estimatedTimeMinutes,
        };

        return match ($unit->value) {
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

    /**
     * Find the optimal branch to fulfill an order
     */
    public function findOptimalBranch(array $orderData, array $orderItems): Store
    {
        $productIds = collect($orderItems)->pluck('product_id')->unique()->toArray();
        $deliveryAddressId = $orderData['delivery_address_id'] ?? null;

        // Get the main store from the request or from the first product
        $mainStoreId = $orderData['store_id'] ?? null;
        if (!$mainStoreId && !empty($productIds)) {
            $firstProduct = \Modules\Product\Models\Product::find($productIds[0]);
            $mainStoreId = $firstProduct?->store_id;
        }

        if (!$mainStoreId) {
            throw new \Exception('Unable to determine store for order');
        }

        $mainStore = Store::find($mainStoreId);
        if (!$mainStore) {
            throw new \Exception('Store not found');
        }

        // Get all branches (including main store)
        $branches = collect([$mainStore])->merge($mainStore->branches);

        // Find branches that can fulfill the order
        $candidateBranches = $branches->filter(function($branch) use ($productIds, $deliveryAddressId) {
            return $this->branchCanFulfillOrder($branch, $productIds, $deliveryAddressId);
        });

        if ($candidateBranches->isEmpty()) {
            throw new \Exception('No branch can fulfill this order. Products may be out of stock or delivery not available.');
        }

        // Select the optimal branch (closest to delivery address)
        return $this->selectOptimalBranch($candidateBranches, $deliveryAddressId);
    }

    /**
     * Check if a branch can fulfill the order
     */
    private function branchCanFulfillOrder(Store $branch, array $productIds, ?int $deliveryAddressId): bool
    {
        // Check if branch has all required products in stock
        foreach ($productIds as $productId) {
            $availability = \Modules\Product\Models\ProductAvailability::where('product_id', $productId)
                ->where('store_id', $branch->id)
                ->first();

            if (!$availability || !$availability->is_in_stock || $availability->stock_quantity <= 0) {
                return false;
            }
        }

        // If delivery address is provided, check if branch can deliver there
        if ($deliveryAddressId) {
            return $branch->canDeliverTo($deliveryAddressId);
        }

        // If no delivery address (pickup order), branch is valid
        return true;
    }

    /**
     * Select the optimal branch from candidates
     */
    private function selectOptimalBranch(\Illuminate\Support\Collection $branches, ?int $deliveryAddressId): Store
    {
        if ($branches->count() === 1) {
            return $branches->first();
        }

        if (!$deliveryAddressId) {
            // For pickup orders, prefer main branch or first available
            return $branches->first(function($branch) {
                return $branch->branch_type === 'main';
            }) ?? $branches->first();
        }

        // For delivery orders, select closest branch
        $deliveryAddress = Address::find($deliveryAddressId);
        if (!$deliveryAddress || !$deliveryAddress->latitude || !$deliveryAddress->longitude) {
            return $branches->first();
        }

        $closestBranch = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($branches as $branch) {
            $branchAddress = $branch->address;
            if (!$branchAddress || !$branchAddress->latitude || !$branchAddress->longitude) {
                continue;
            }

            $distance = $this->calculateDistance(
                $deliveryAddress->latitude,
                $deliveryAddress->longitude,
                $branchAddress->latitude,
                $branchAddress->longitude
            );

            if ($distance < $minDistance) {
                $minDistance = $distance;
                $closestBranch = $branch;
            }
        }

        return $closestBranch ?? $branches->first();
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

            // Restore stock to the correct branch
            foreach ($order->orderItems as $item) {
                $availability = \Modules\Product\Models\ProductAvailability::where('product_id', $item->product_id)
                    ->where('store_id', $order->store_id)
                    ->first();

                if ($availability) {
                    $availability->increment('stock_quantity', $item->quantity);
                    $availability->update(['is_in_stock' => true]);
                }
            }

            return $this->orderRepository->delete($id);
        });
    }
}
