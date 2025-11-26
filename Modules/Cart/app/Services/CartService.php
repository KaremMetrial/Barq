<?php

namespace Modules\Cart\Services;

use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\AddOn\Models\AddOn;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Modules\Cart\Repositories\CartRepository;
use Modules\Product\Models\ProductOptionValue;

class CartService
{
    public function __construct(protected CartRepository $cartRepository) {}

    private function validateStoreConsistency(Cart $cart, array $items): void
    {
        // Get all product IDs from the request
        $productIds = collect($items)->pluck('product_id')->unique()->filter()->values()->all();

        // Get products with their store information
        $products = Product::whereIn('id', $productIds)
            ->with('store:id')
            ->get()
            ->keyBy('id');

        // Get the cart's current store (from existing items or cart.store_id)
        $cartStoreId = $cart->store_id;

        if (!$cartStoreId && $cart->items->count() > 0) {
            // Get store from first item if cart doesn't have store_id set
            $firstItem = $cart->items->first();
            if ($firstItem && $firstItem->product) {
                $cartStoreId = $firstItem->product->store_id;
            }
        }

        // Check if all products belong to the same store
        $storeIds = $products->pluck('store_id')->unique()->filter()->values();

        if ($storeIds->count() > 1) {
            throw new \InvalidArgumentException('validation_store');
        }

        // If cart already has a store, ensure new products match
        if ($cartStoreId && $storeIds->count() > 0) {
            $newStoreId = $storeIds->first();
            if ($newStoreId !== $cartStoreId) {
                throw new \InvalidArgumentException('validation_store');
            }
        }
    }

    public function getCart()
    {
        $cartKey = request()->header('Cart-Key') ?? request('cart_key');

        if ($cartKey) {
            return $this->getCartByCartKey($cartKey);
        }

        return null;
    }


    public function createCart(array $data): ?Cart
    {
        return DB::transaction(function () use ($data) {
            $cartData = $data['cart'] ?? [];
            $cartData['cart_key'] = Str::uuid()->toString();
            $cartData['user_id'] = auth('user')->id();
            if (empty($cartData['store_id']) && !empty($data['items'])) {
                $firstProductId = collect($data['items'])->pluck('product_id')->first();
                if ($firstProductId) {
                    $product = Product::find($firstProductId);
                    if ($product) {
                        $cartData['store_id'] = $product->store_id;
                    }
                }
            }

            $filteredCartData = array_filter($cartData, fn($value) => !blank($value));
            $cart = $this->cartRepository->create($filteredCartData);

            if (!empty($data['items'] ?? [])) {
                $this->validateStoreConsistency($cart, $data['items']);
            }

            $this->syncCartItems($cart, $data['items'] ?? []);
            return $cart->refresh();
        });
    }

    public function getCartById(int $id): ?Cart
    {
        return $this->cartRepository->find(
            $id,
            [
                'items.product',
                'items.addOns',
                'items.productOptionValue.productOption.values',
                'items.addedBy',
                'store',
                'user',
                'participants',
                'posShift',
            ]
        );
    }

    public function getCartByCartKey(string $cartKey): ?Cart
    {
        return $this->cartRepository->firstWhere(
            ['cart_key' => $cartKey]
        );
    }

    public function updateCart(string $key, array $data): ?Cart
    {
        return DB::transaction(function () use ($key, $data) {
            $cart = $this->getCartByCartKey($key);
            if (!$cart) {
                $cart = $this->getCartByCartKey(request()->header('Cart-Key') ?? request('cart_key'));
            }
            if (!$cart) {
                return null;
            }
            if (!empty($data['items'] ?? [])) {
                $this->validateStoreConsistency($cart, $data['items']);
            }

            $filteredCartData = array_filter($data['cart'] ?? [], fn($value) => !blank($value));

            $this->cartRepository->update($cart->id, $filteredCartData);

            $this->syncCartItems($cart, $data['items'] ?? []);

            return $cart->refresh();
        });
    }

    public function deleteCart(int $id): bool
    {
        return $this->cartRepository->delete($id);
    }
    private function syncCartItems(Cart $cart, array $items): void
    {
        if (empty($items)) {
            return;
        }

        // Deduplicate items based on product_id, product_option_value_id, and add_ons
        $items = collect($items)->groupBy(function ($item) {
            $optionKey = is_array($item['product_option_value_id'] ?? null)
                ? json_encode($item['product_option_value_id'])
                : ($item['product_option_value_id'] ?? 'null');
            $addOnKey = json_encode(collect($item['add_ons'] ?? [])->sortBy('id')->pluck('id')->values()->all());
            return $item['product_id'] . '-' . $optionKey . '-' . $addOnKey;
        })->map(function ($group) {
            $first = $group->first();
            $totalQuantity = $group->sum('quantity');
            return $first + ['quantity' => $totalQuantity];
        })->values()->all();

        $this->validateStoreConsistency($cart, $items);

        // Validate and collect IDs
        $productIds = collect($items)->pluck('product_id')->unique()->filter()->values()->all();
        $optionValueIds = collect($items)->pluck('product_option_value_id')->filter(function ($value) {
            return !is_null($value) && $value !== '';
        })->flatten()->unique()->values()->all();
        $addOnIds = collect($items)
            ->flatMap(fn($item) => $item['add_ons'] ?? [])
            ->pluck('id')
            ->unique()
            ->filter()
            ->values()
            ->all();
        // Load models with validation
        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $options = ProductOptionValue::whereIn('id', $optionValueIds)->get()->keyBy('id');
        $addOnModels = AddOn::whereIn('id', $addOnIds)->get()->keyBy('id');

        $existingItems = $cart->items()->get()->keyBy(function ($item) {
            $optionKey = is_array($item->product_option_value_id) ? json_encode($item->product_option_value_id) : ($item->product_option_value_id ?? 'null');
            return $item->product_id . '-' . $optionKey;
        });
        foreach ($items as $item) {
            $optionValueId = $item['product_option_value_id'] ?? null;
            $key = $item['product_id'] . '-' . (is_array($optionValueId) ? json_encode($optionValueId) : ($optionValueId ?? 'null'));
            if (isset($item['quantity']) && (int) $item['quantity'] == 0) {
                // If product_option_value_id is null, delete all items with this product_id
                if (is_null($optionValueId)) {
                    $productId = $item['product_id'];
                    $itemsToDelete = $existingItems->filter(function ($cartItem) use ($productId) {
                        return $cartItem->product_id == $productId;
                    });

                    foreach ($itemsToDelete as $itemToDelete) {
                        $itemToDelete->delete();
                    }
                } else {
                    $found = false;

                    if ($existingItems->has($key)) {
                        $existingItems->get($key)->delete();
                        $found = true;
                    }

                    if (!$found) {
                        $altKeys = [
                            $item['product_id'] . '-' . json_encode([$optionValueId]),
                            $item['product_id'] . '-["' . (is_array($optionValueId) ? implode('","', $optionValueId) : $optionValueId) . '"]',
                            $item['product_id'] . '-' . (is_array($optionValueId) ? '[' . implode(',', $optionValueId) . ']' : $optionValueId),
                        ];

                        foreach ($altKeys as $altKey) {
                            if ($existingItems->has($altKey)) {
                                $existingItems->get($altKey)->delete();
                                $found = true;
                                break;
                            }
                        }
                    }
                }

                continue;
            }

            // Check if item exists with exact key match
            if ($existingItems->has($key)) {
                // Update existing item - keep existing options if not provided
                $existingItem = $existingItems->get($key);
                $cartItemData = $this->prepareCartItemData($item, $products, $options, $addOnModels, $existingItem);
                $existingItem->update($cartItemData);

                // Only update add-ons if explicitly provided in the request
                if (isset($item['add_ons'])) {
                    if (!empty($item['add_ons'])) {
                        $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                        $existingItem->addOns()->sync($addOnPivotData);
                    } else {
                        $existingItem->addOns()->detach();
                    }
                }
                // If add_ons not provided, keep existing add-ons (like options)
            } else {
                // If no exact match and no options specified, try to find existing item with same product_id
                $existingItemWithProduct = null;
                if (is_null($optionValueId)) {
                    $productId = $item['product_id'];
                    $existingItemWithProduct = $existingItems->first(function ($cartItem) use ($productId) {
                        return $cartItem->product_id == $productId;
                    });
                }

                if ($existingItemWithProduct) {
                    // Update the existing item with the same product (regardless of options) - keep existing options
                    $cartItemData = $this->prepareCartItemData($item, $products, $options, $addOnModels, $existingItemWithProduct);
                    $existingItemWithProduct->update($cartItemData);

                    // Only update add-ons if explicitly provided in the request
                    if (isset($item['add_ons'])) {
                        if (!empty($item['add_ons'])) {
                            $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                            $existingItemWithProduct->addOns()->sync($addOnPivotData);
                        } else {
                            $existingItemWithProduct->addOns()->detach();
                        }
                    }
                    // If add_ons not provided, keep existing add-ons (like options)
                } else {
                    // Create new item
                    $cartItemData = $this->prepareCartItemData($item, $products, $options, $addOnModels);
                    $cartItemData['added_by_user_id'] = auth('user')->id();
                    $newCartItem = $cart->items()->create($cartItemData);

                    if (!empty($item['add_ons'])) {
                        $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                        $newCartItem->addOns()->sync($addOnPivotData);
                    }
                }
            }
        }
    }



    private function prepareCartItemData(
        array $item,
        Collection $products,
        Collection $options,
        Collection $addOnModels,
        $existingItem = null
    ): array {
        // Validate required fields
        if (!isset($item['product_id'])) {
            throw new \InvalidArgumentException('Product ID is required for cart item');
        }

        $quantity = max(1, (int)($item['quantity'] ?? 1)); // Ensure positive quantity
        $product = $products[$item['product_id']] ?? null;
        $option = null; // Since it's now an array, we can't directly assign a single option
        // Note: If multiple options are selected, pricing logic needs to be updated accordingly

        // Validate product exists and is available
        if (!$product) {
            throw new \InvalidArgumentException("Product with ID {$item['product_id']} not found");
        }

        // Validate quantity against max_cart_quantity
        if ($product->max_cart_quantity && $quantity > $product->max_cart_quantity) {
            throw new \InvalidArgumentException("Maximum quantity for this product is {$product->max_cart_quantity}");
        }

        // Get product price safely
        $productPrice = 0;
        if ($product->price && isset($product->price->price)) {
            $productPrice = (float) $product->price->price;
        }

        // Use existing item's options if no options provided in update
        $optionValueId = $item['product_option_value_id'] ?? ($existingItem ? $existingItem->product_option_value_id : null);

        $optionPrice = 0; // Initialize to 0 since options are now an array
        if (is_array($optionValueId)) {
            foreach ($optionValueId as $optionId) {
                $opt = $options[$optionId] ?? null;
                if ($opt) {
                    $optionPrice += (float) $opt->price;
                }
            }
        } elseif ($optionValueId) {
            $opt = $options[$optionValueId] ?? null;
            $optionPrice = $opt ? (float) $opt->price : 0;
        }

        // Use existing item's add-ons if no add-ons provided in update
        $itemAddOns = $item['add_ons'] ?? ($existingItem ? $existingItem->addOns->map(function ($addOn) {
            return [
                'id' => $addOn->id,
                'quantity' => $addOn->pivot->quantity ?? 1,
                'price' => $addOn->pivot->price_modifier ?? $addOn->price
            ];
        })->toArray() : []);

        $addOnTotal = collect($itemAddOns)
            ->sum(function ($addOn) use ($addOnModels) {
                $addOnModel = $addOnModels[$addOn['id']] ?? null;
                if (!$addOnModel) {
                    // Skip add-ons that are not found in the loaded models (might be deleted or inactive)
                    return 0;
                }
                return (float) $addOnModel->price * max(1, (int)($addOn['quantity'] ?? 1));
            });

        $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

        // Apply product offers
        $offers = $product->offers()->where('is_active', true)->where('start_date', '<=', now())->where('end_date', '>=', now())->get();
        if ($offers->count() > 0) {
            $offer = $offers->first(); // Take the first active offer
            if ($offer->discount_type->value === 'percentage') {
                $totalPrice = $totalPrice * (1 - $offer->discount_amount / 100);
            } else {
                $totalPrice = max(0, $totalPrice - $offer->discount_amount);
            }
        }

        return [
            'product_id' => $item['product_id'],
            'product_option_value_id' => $optionValueId,
            'quantity' => $quantity,
            'note' => $item['note'] ?? null,
            'total_price' => round($totalPrice, 2), // Round to 2 decimal places
        ];
    }

    private function prepareAddOnPivotData(array $addOns, Collection $addOnModels): array
    {
        return collect($addOns)->mapWithKeys(function ($addOn) use ($addOnModels) {
            $model = $addOnModels[$addOn['id']] ?? null;
            if (!$model) {
                throw new \InvalidArgumentException("Add-on with ID {$addOn['id']} not found");
            }

            $quantity = max(1, (int)($addOn['quantity'] ?? 1));

            return [
                $addOn['id'] => [
                    'quantity' => $quantity,
                    'price_modifier' => round((float) $model->price * $quantity, 2),
                ],
            ];
        })->toArray();
    }

    private function recalculateCartItemTotalPrice($cartItem, Collection $products, Collection $options, Collection $addOnModels): void
    {
        $product = $products[$cartItem->product_id] ?? null;
        if (!$product) {
            return;
        }

        // Get product price safely
        $productPrice = 0;
        if ($product->price && isset($product->price->price)) {
            $productPrice = (float) $product->price->price;
        }

        // Calculate option price
        $optionPrice = 0;
        $optionValueId = $cartItem->product_option_value_id;
        if (is_string($optionValueId)) {
            $optionValueId = json_decode($optionValueId, true);
        }

        if (is_array($optionValueId)) {
            foreach ($optionValueId as $optionId) {
                $opt = $options[$optionId] ?? null;
                if ($opt) {
                    $optionPrice += (float) $opt->price;
                }
            }
        } elseif ($optionValueId) {
            $opt = $options[$optionValueId] ?? null;
            $optionPrice = $opt ? (float) $opt->price : 0;
        }

        // Calculate add-on total
        $addOnTotal = $cartItem->addOns->sum(function ($addOn) {
            return (float) $addOn->pivot->price_modifier;
        });

        $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $cartItem->quantity;

        // Apply product offers
        $offers = $product->offers()->where('is_active', true)->where('start_date', '<=', now())->where('end_date', '>=', now())->get();
        if ($offers->count() > 0) {
            $offer = $offers->first(); // Take the first active offer
            if ($offer->discount_type->value === 'percentage') {
                $totalPrice = $totalPrice * (1 - $offer->discount_amount / 100);
            } else {
                $totalPrice = max(0, $totalPrice - $offer->discount_amount);
            }
        }

        $cartItem->update(['total_price' => round($totalPrice, 2)]);
    }
    public function getShareById(int $id)
    {
        $cart = $this->getCartById($id);
        $cart->is_group_order = true;
        $cart->save();

        $shareableLink = route('api.cart.join', ['cart_key' => $cart->cart_key]);

        return $shareableLink;
    }
    public function joinCart($cartKey)
    {
        $cart = $this->cartRepository->firstWhere(['cart_key' => $cartKey]);

        if (!$cart) {
            return null;
        }

        // Check if user is already a participant
        if ($cart->participants()->where('user_id', auth('user')->id())->exists()) {
            return $cart; // Already joined
        }

        $cart->participants()->attach(auth('user')->id());
        $cart->save();
        return $cart;
    }

    public function removeParticipant(string $cartKey, int $userId): ?Cart
    {
        $cart = $this->cartRepository->firstWhere(['cart_key' => $cartKey]);

        if (!$cart) {
            return null;
        }

        // Check if the current user is the owner of the cart
        if ($cart->user_id !== auth('user')->id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('Only the cart owner can remove participants.');
        }

        // Check if the user to remove is a participant
        if (!$cart->participants()->where('user_id', $userId)->exists()) {
            throw new \InvalidArgumentException('User is not a participant in this cart.');
        }

        // Cannot remove the owner themselves
        if ($userId === $cart->user_id) {
            throw new \InvalidArgumentException('Cannot remove the cart owner from participants.');
        }

        $cart->participants()->detach($userId);
        $cart->save();
        return $cart;
    }
        public function isDeliveryToThisArea($cart, $address): bool
    {
        if (!$cart->store) {
            return false;
        }
        if (!$address) {
            return false;
        }
        return $cart->store->canDeliverTo($address->id);
    }
}
