<?php

namespace Modules\Cart\Services;

use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Cart\Repositories\CartRepository;
use Modules\Product\Models\ProductOptionValue;

class CartService
{
    public function __construct(
        protected CartRepository $CartRepository
    ) {}

    public function getAllCarts(): Collection
    {
        return $this->CartRepository->all();
    }

    public function createCart(array $data): ?Cart
    {
        return DB::transaction(function () use ($data) {
            $data['cart']['cart_key'] = Str::uuid()->toString();
            $data = array_filter($data, fn($value) => !blank($value));
            $cart = $this->CartRepository->create($data['cart']);

            $this->syncCartItem($cart, $data['items'] ?? []);
            return $cart->refresh();
        });
    }

    public function getCartById(int $id)
    {
        return $this->CartRepository->find($id, ['items.product', 'items.productOptionValue.productOption.values', 'store', 'user', 'posShift']);
    }

    public function updateCart(int $id, array $data): ?Cart
    {
        return DB::transaction(function () use ($id, $data) {
            $data = array_filter($data, fn($value) => !blank($value));
            $cart = $this->CartRepository->update($id, $data['cart'] ?? []);

            $this->syncCartItem($cart, $data['items'] ?? []);
            return $cart->refresh();
        });
    }

    public function deleteCart(int $id): bool
    {
        return $this->CartRepository->delete($id);
    }
    private function syncCartItem(Cart $cart, array $items): void
    {
        $preparedItems = $this->prepareCartItems($items);

        if (!empty($preparedItems)) {
            $cart->items()->createMany($preparedItems);
        }
    }
    private function prepareCartItems(array $items): array
    {
        if (empty($items)) return [];

        $productIds = collect($items)->pluck('product_id')->unique()->all();
        $optionValueIds = collect($items)->pluck('product_option_value_id')->filter()->unique()->all();

        $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
        $options = ProductOptionValue::whereIn('id', $optionValueIds)->get()->keyBy('id');

        return collect($items)->map(function ($item) use ($products, $options) {
            $product = $products[$item['product_id']] ?? null;
            if (!$product) return null;

            $quantity = $item['quantity'] ?? 1;
            $optionId = $item['product_option_value_id'] ?? null;
            $option = $optionId ? ($options[$optionId] ?? null) : null;

            $addOns = collect($item['add_ons'] ?? []);

            $totalPrice = $this->calculateTotalPrice(
                productPrice: $product->price->price ?? 0,
                optionPrice: $option?->price ?? 0,
                addOns: $addOns,
                quantity: $quantity
            );

            return [
                'product_id' => $item['product_id'],
                'product_option_value_id' => $optionId,
                'quantity' => $quantity,
                'note' => $item['note'] ?? null,
                'total_price' => $totalPrice,
            ];
        })->filter()->values()->all();
    }

    private function calculateTotalPrice(float $productPrice, float $optionPrice, \Illuminate\Support\Collection $addOns, int $quantity): float
    {
        $addOnTotal = $addOns->map(function ($addOn) {
            return ($addOn['price'] ?? 0) * ($addOn['quantity'] ?? 1);
        })->sum();

        return ($productPrice + $optionPrice + $addOnTotal) * $quantity;
    }
}
