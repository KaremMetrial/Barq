<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Cart\Services\CartService;
use Modules\Cart\Models\Cart;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use ReflectionClass;
use ReflectionMethod;

class CartIncrementTest extends TestCase
{
    /** @test */
    public function it_increments_quantity_when_is_increment_is_true()
    {
        $refClass = new ReflectionClass(CartService::class);
        $service = $refClass->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(CartService::class, 'prepareCartItemData');
        $method->setAccessible(true);

        $cart = new Cart();
        $cart->store_id = 1;

        $product = new Product();
        $product->id = 101;
        $product->max_cart_quantity = 10;

        $products = new Collection([101 => $product]);
        $options = new Collection();
        $addOnModels = new Collection();

        $item = ['product_id' => 101, 'quantity' => 2];
        $existingItem = (object) ['quantity' => 1, 'product_id' => 101, 'product_option_value_id' => null];

        // First test without increment
        $result = $method->invoke($service, $cart, $item, $products, $options, $addOnModels, $existingItem, false);
        $this->assertEquals(2, $result['quantity']);

        // Now test with increment
        $result = $method->invoke($service, $cart, $item, $products, $options, $addOnModels, $existingItem, true);
        $this->assertEquals(3, $result['quantity']);
    }

    /** @test */
    public function it_does_not_increment_when_no_existing_item()
    {
        $refClass = new ReflectionClass(CartService::class);
        $service = $refClass->newInstanceWithoutConstructor();

        $method = new ReflectionMethod(CartService::class, 'prepareCartItemData');
        $method->setAccessible(true);

        $cart = new Cart();
        $product = new Product();
        $product->id = 101;
        $products = new Collection([101 => $product]);

        $item = ['product_id' => 101, 'quantity' => 2];

        $result = $method->invoke($service, $cart, $item, $products, new Collection(), new Collection(), null, true);
        $this->assertEquals(2, $result['quantity']);
    }
}
