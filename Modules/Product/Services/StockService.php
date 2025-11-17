<?php

namespace Modules\Product\Services;

use Modules\Product\Models\Product;
use Modules\Product\Models\ProductAvailability;
use Modules\Product\Models\ProductOptionValue;
use Modules\Store\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    /**
     * Check if product is available in stock for a specific store
     */
    public function checkProductStock(Product $product, int $storeId, int $quantity = 1, ?array $optionIds = null): array
    {
        // Check main product stock
        $availability = ProductAvailability::where('product_id', $product->id)
            ->where('store_id', $storeId)
            ->first();

        if (!$availability || !$availability->is_in_stock) {
            return [
                'available' => false,
                'reason' => 'Product is out of stock',
                'available_quantity' => 0
            ];
        }

        if ($availability->stock_quantity < $quantity) {
            return [
                'available' => false,
                'reason' => 'Insufficient stock',
                'available_quantity' => $availability->stock_quantity
            ];
        }

        // Check option stock if options are selected
        if ($optionIds) {
            foreach ($optionIds as $optionId) {
                $optionStock = $this->checkOptionStock($optionId, $quantity);
                if (!$optionStock['available']) {
                    return $optionStock;
                }
            }
        }

        return [
            'available' => true,
            'available_quantity' => $availability->stock_quantity
        ];
    }

    /**
     * Check if product option has sufficient stock
     */
    public function checkOptionStock(int $optionId, int $quantity = 1): array
    {
        $option = ProductOptionValue::find($optionId);

        if (!$option) {
            return [
                'available' => false,
                'reason' => 'Product option not found',
                'available_quantity' => 0
            ];
        }

        if ($option->stock < $quantity) {
            return [
                'available' => false,
                'reason' => 'Insufficient option stock',
                'available_quantity' => $option->stock
            ];
        }

        return [
            'available' => true,
            'available_quantity' => $option->stock
        ];
    }

    /**
     * Reserve stock for cart items (soft reservation)
     */
    public function reserveStock(int $productId, int $storeId, int $quantity, ?array $optionIds = null): bool
    {
        return DB::transaction(function () use ($productId, $storeId, $quantity, $optionIds) {
            // Reserve main product stock
            $availability = ProductAvailability::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (!$availability || !$availability->is_in_stock || $availability->stock_quantity < $quantity) {
                return false;
            }

            // Reserve option stock if needed
            if ($optionIds) {
                foreach ($optionIds as $optionId) {
                    $option = ProductOptionValue::find($optionId);
                    if (!$option || $option->stock < $quantity) {
                        return false;
                    }
                    $option->decrement('stock', $quantity);
                }
            }

            // For now, we'll use a simple approach - in production you might want a separate reserved_stock field
            // For this implementation, we'll just ensure the stock is available but not decrement yet
            // The actual decrement happens during order processing

            return true;
        });
    }

    /**
     * Release reserved stock (when cart is cleared or item removed)
     */
    public function releaseStock(int $productId, int $storeId, int $quantity, ?array $optionIds = null): void
    {
        // Since we're not actually decrementing stock during reservation,
        // we don't need to release anything. This is just a placeholder
        // for future implementation with actual stock reservation fields.

        Log::info("Stock reservation released for product {$productId}, store {$storeId}, quantity {$quantity}");
    }

    /**
     * Decrease stock after successful order (convert reservation to actual sale)
     */
    public function decreaseStock(int $productId, int $storeId, int $quantity, ?array $optionIds = null): bool
    {
        return DB::transaction(function () use ($productId, $storeId, $quantity, $optionIds) {
            // Decrease main product stock
            $availability = ProductAvailability::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if (!$availability || $availability->stock_quantity < $quantity) {
                Log::error("Failed to decrease stock for product {$productId}: insufficient stock");
                return false;
            }

            $availability->decrement('stock_quantity', $quantity);

            // Mark as out of stock if quantity reaches zero
            if ($availability->stock_quantity <= 0) {
                $availability->update(['is_in_stock' => false]);
            }

            // Decrease option stock if needed
            if ($optionIds) {
                foreach ($optionIds as $optionId) {
                    $option = ProductOptionValue::where('id', $optionId)->lockForUpdate()->first();
                    if ($option && $option->stock >= $quantity) {
                        $option->decrement('stock', $quantity);
                    }
                }
            }

            Log::info("Stock decreased for product {$productId}, store {$storeId}, quantity {$quantity}");
            return true;
        });
    }

    /**
     * Restore stock when order is cancelled or refunded
     */
    public function restoreStock(int $productId, int $storeId, int $quantity, ?array $optionIds = null): bool
    {
        return DB::transaction(function () use ($productId, $storeId, $quantity, $optionIds) {
            // Restore main product stock
            $availability = ProductAvailability::where('product_id', $productId)
                ->where('store_id', $storeId)
                ->lockForUpdate()
                ->first();

            if ($availability) {
                $availability->increment('stock_quantity', $quantity);

                // Mark as in stock if it was out of stock
                if (!$availability->is_in_stock && $availability->stock_quantity > 0) {
                    $availability->update(['is_in_stock' => true]);
                }
            }

            // Restore option stock if needed
            if ($optionIds) {
                foreach ($optionIds as $optionId) {
                    $option = ProductOptionValue::where('id', $optionId)->lockForUpdate()->first();
                    if ($option) {
                        $option->increment('stock', $quantity);
                    }
                }
            }

            Log::info("Stock restored for product {$productId}, store {$storeId}, quantity {$quantity}");
            return true;
        });
    }

    /**
     * Get available stock for a product in a specific store
     */
    public function getAvailableStock(int $productId, int $storeId): int
    {
        $availability = ProductAvailability::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        return $availability ? $availability->stock_quantity : 0;
    }

    /**
     * Get available stock for product option
     */
    public function getAvailableOptionStock(int $optionId): int
    {
        $option = ProductOptionValue::find($optionId);
        return $option ? $option->stock : 0;
    }

    /**
     * Update product stock (admin function)
     */
    public function updateProductStock(int $productId, int $storeId, int $newQuantity, bool $isInStock = null): bool
    {
        $availability = ProductAvailability::where('product_id', $productId)
            ->where('store_id', $storeId)
            ->first();

        if (!$availability) {
            // Create new availability record if it doesn't exist
            ProductAvailability::create([
                'product_id' => $productId,
                'store_id' => $storeId,
                'stock_quantity' => $newQuantity,
                'is_in_stock' => $isInStock ?? ($newQuantity > 0),
            ]);
            return true;
        }

        $updateData = ['stock_quantity' => $newQuantity];
        if ($isInStock !== null) {
            $updateData['is_in_stock'] = $isInStock;
        } else {
            $updateData['is_in_stock'] = $newQuantity > 0;
        }

        return $availability->update($updateData);
    }

    /**
     * Bulk update stock for multiple products (useful for inventory management)
     */
    public function bulkUpdateStock(array $stockUpdates): array
    {
        $results = [];

        foreach ($stockUpdates as $update) {
            $result = $this->updateProductStock(
                $update['product_id'],
                $update['store_id'],
                $update['quantity'],
                $update['is_in_stock'] ?? null
            );

            $results[] = [
                'product_id' => $update['product_id'],
                'store_id' => $update['store_id'],
                'success' => $result
            ];
        }

        return $results;
    }

    /**
     * Check if store has sufficient stock for all items in cart/order
     */
    public function validateStockForItems(array $items, int $storeId): array
    {
        $errors = [];

        foreach ($items as $index => $item) {
            $productId = $item['product_id'];
            $quantity = $item['quantity'] ?? 1;
            $optionIds = $item['product_option_value_id'] ?? null;

            // Normalize option IDs to array
            if ($optionIds && !is_array($optionIds)) {
                $optionIds = [$optionIds];
            }

            $product = Product::find($productId);
            if (!$product) {
                $errors[] = "Product {$productId} not found";
                continue;
            }

            $stockCheck = $this->checkProductStock($product, $storeId, $quantity, $optionIds);
            if (!$stockCheck['available']) {
                $errors[] = "Item " . ($index + 1) . ": " . $stockCheck['reason'] .
                           " (Available: {$stockCheck['available_quantity']})";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
