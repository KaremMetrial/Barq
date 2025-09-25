<?php

namespace Modules\Cart\Services;

use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Modules\Cart\Repositories\CartRepository;

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
            dd($cart);
            return $cart->refresh();
        });
    }

    public function getCartById(int $id)
    {
        return $this->CartRepository->find($id);
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
        if (!empty($items)) {
            $cart->items()->createMany($items);
        }

    }
}
