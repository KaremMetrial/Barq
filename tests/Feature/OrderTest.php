<?php

use Modules\Order\Models\Order;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductOptionValue;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('can create order with single product option', function () {
    $user = \Modules\User\Models\User::first();
    $store = \Modules\Store\Models\Store::first();
    $product = \Modules\Product\Models\Product::first();
    $option = \Modules\Product\Models\ProductOptionValue::first();

    if (!$user || !$store || !$product || !$option) {
        $this->markTestSkipped('Required data not found in database');
    }

    $orderData = [
        'order' => [
            'store_id' => $store->id,
            'type' => 'pickup',
            'note' => 'Test order',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'product_option_value_id' => [$option->id],
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user')
        ->postJson('/api/orders', $orderData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', ['store_id' => $store->id]);
    $this->assertDatabaseHas('order_items', ['product_option_value_id' => json_encode([$option->id])]);
});

test('can create order with multiple product options', function () {
    $user = \Modules\User\Models\User::first();
    $store = \Modules\Store\Models\Store::first();
    $product = \Modules\Product\Models\Product::first();
    $option1 = \Modules\Product\Models\ProductOptionValue::first();
    $option2 = \Modules\Product\Models\ProductOptionValue::skip(1)->first();

    if (!$user || !$store || !$product || !$option1 || !$option2) {
        $this->markTestSkipped('Required data not found in database');
    }

    $orderData = [
        'order' => [
            'store_id' => $store->id,
            'type' => 'pickup',
            'note' => 'Test order with multiple options',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'product_option_value_id' => [$option1->id, $option2->id],
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user')
        ->postJson('/api/orders', $orderData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', ['store_id' => $store->id]);
    $this->assertDatabaseHas('order_items', ['product_option_value_id' => json_encode([$option1->id, $option2->id])]);
});

test('can create order without product options', function () {
    $user = \Modules\User\Models\User::first();
    $store = \Modules\Store\Models\Store::first();
    $product = \Modules\Product\Models\Product::first();

    if (!$user || !$store || !$product) {
        $this->markTestSkipped('Required data not found in database');
    }

    $orderData = [
        'order' => [
            'store_id' => $store->id,
            'type' => 'pickup',
            'note' => 'Test order without options',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ];

    $response = $this->actingAs($user, 'user')
        ->postJson('/api/orders', $orderData);

    $response->assertStatus(201);
    $this->assertDatabaseHas('orders', ['store_id' => $store->id]);
    $this->assertDatabaseHas('order_items', ['product_option_value_id' => null]);
});
