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

            $filteredCartData = array_filter($cartData, fn($value) => !blank($value));
            $cart = $this->cartRepository->create($filteredCartData);

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

    public function updateCart(int $id, array $data): ?Cart
    {
        return DB::transaction(function () use ($id, $data) {
            $cart = $this->cartRepository->find($id);

            if (!$cart) {
                return null;
            }
            $filteredCartData = array_filter($data['cart'] ?? [], fn($value) => !blank($value));

            $this->cartRepository->update($id, $filteredCartData);

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

        // Validate and collect IDs
        $productIds = collect($items)->pluck('product_id')->unique()->filter()->values()->all();
        $optionValueIds = collect($items)->pluck('product_option_value_id')->filter()->unique()->values()->all();
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
            return $item->product_id . '-' . ($item->product_option_value_id ?? 'null');
        });

        foreach ($items as $item) {
            $key = $item['product_id'] . '-' . ($item['product_option_value_id'] ?? 'null');

            if (($item['quantity'] ?? 1) == 0) {
                if ($existingItems->has($key)) {
                    $existingItems->get($key)->delete();
                }
                continue;
            }

            $cartItemData = $this->prepareCartItemData($item, $products, $options, $addOnModels);

            if ($existingItems->has($key)) {
                $existingItem = $existingItems->get($key);
                $existingItem->update($cartItemData);

                if (!empty($item['add_ons'])) {
                    $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                    $existingItem->addOns()->sync($addOnPivotData);
                } else {
                    $existingItem->addOns()->detach();
                }
            } else {
                $cartItemData['added_by_user_id'] = auth('user')->id();
                $newCartItem = $cart->items()->create($cartItemData);

                if (!empty($item['add_ons'])) {
                    $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                    $newCartItem->addOns()->sync($addOnPivotData);
                }
            }
        }
    }



    private function prepareCartItemData(
        array $item,
        Collection $products,
        Collection $options,
        Collection $addOnModels
    ): array {
        // Validate required fields
        if (!isset($item['product_id'])) {
            throw new \InvalidArgumentException('Product ID is required for cart item');
        }

        $quantity = max(1, (int)($item['quantity'] ?? 1)); // Ensure positive quantity
        $product = $products[$item['product_id']] ?? null;
        $option = $options[$item['product_option_value_id'] ?? null] ?? null;

        // Validate product exists and is available
        if (!$product) {
            throw new \InvalidArgumentException("Product with ID {$item['product_id']} not found");
        }

        // Get product price safely
        $productPrice = 0;
        if ($product->price && isset($product->price->price)) {
            $productPrice = (float) $product->price->price;
        }

        $optionPrice = $option ? (float) $option->price : 0;

        $addOnTotal = collect($item['add_ons'] ?? [])
            ->sum(function ($addOn) use ($addOnModels) {
                $addOnModel = $addOnModels[$addOn['id']] ?? null;
                if (!$addOnModel) {
                    throw new \InvalidArgumentException("Add-on with ID {$addOn['id']} not found");
                }
                return (float) $addOnModel->price * max(1, (int)($addOn['quantity'] ?? 1));
            });

        $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

        return [
            'product_id' => $item['product_id'],
            'product_option_value_id' => $item['product_option_value_id'] ?? null,
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
        if ($cart->participants()->where('user_id', auth()->id())->exists()) {
            return $cart; // Already joined
        }

        $cart->participants()->attach(auth()->id());
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
}
