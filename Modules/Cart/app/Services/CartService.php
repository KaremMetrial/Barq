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

    public function getAllCarts(): Collection
    {
        return $this->cartRepository->all();
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
                'store',
                'user',
                'posShift',
            ]
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

        $productIds = collect($items)->pluck('product_id')->unique()->values()->all();
        $optionValueIds = collect($items)->pluck('product_option_value_id')->filter()->unique()->values()->all();
        $addOnIds = collect($items)
            ->flatMap(fn($item) => $item['add_ons'] ?? [])
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $options = ProductOptionValue::whereIn('id', $optionValueIds)->get()->keyBy('id');
        $addOnModels = AddOn::whereIn('id', $addOnIds)->get()->keyBy('id');

        $cart->items()->delete();

        foreach ($items as $item) {
            $cartItemData = $this->prepareCartItemData($item, $products, $options, $addOnModels);
            $cartItem = $cart->items()->create($cartItemData);

            if (!empty($item['add_ons'])) {
                $addOnPivotData = $this->prepareAddOnPivotData($item['add_ons'], $addOnModels);
                $cartItem->addOns()->sync($addOnPivotData);
            }
        }
    }

    private function prepareCartItemData(
        array $item,
        Collection $products,
        Collection $options,
        Collection $addOnModels
    ): array {
        $quantity = $item['quantity'] ?? 1;
        $product = $products[$item['product_id']] ?? null;
        $option = $options[$item['product_option_value_id'] ?? null] ?? null;

        $productPrice = $product?->price->price ?? 0;
        $optionPrice = $option?->price ?? 0;

        $addOnTotal = collect($item['add_ons'] ?? [])
            ->sum(
                fn($addOn) => ($addOnModels[$addOn['id']]->price ?? 0) * ($addOn['quantity'] ?? 1)
            );

        $totalPrice = ($productPrice + $optionPrice + $addOnTotal) * $quantity;

        return [
            'product_id' => $item['product_id'],
            'product_option_value_id' => $item['product_option_value_id'] ?? null,
            'quantity' => $quantity,
            'note' => $item['note'] ?? null,
            'total_price' => $totalPrice,
        ];
    }

    private function prepareAddOnPivotData(array $addOns, Collection $addOnModels): array
    {
        return collect($addOns)->mapWithKeys(function ($addOn) use ($addOnModels) {
            $model = $addOnModels[$addOn['id']] ?? null;
            if (!$model) {
                return [];
            }

            $quantity = $addOn['quantity'] ?? 1;

            return [
                $addOn['id'] => [
                    'quantity' => $quantity,
                    'price_modifier' => $model->price * $quantity,
                ],
            ];
        })->toArray();
    }
}
