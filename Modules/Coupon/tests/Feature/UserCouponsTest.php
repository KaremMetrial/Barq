<?php

namespace Modules\Coupon\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Product\Models\Product;
use Modules\Store\Models\Store;
use Modules\Coupon\Models\Coupon;
use Modules\Reward\Models\Reward;
use Laravel\Sanctum\Sanctum;

class UserCouponsTest extends TestCase
{
    /** @test */
    public function it_returns_coupons_from_user_rewards()
    {
        // Create user
        $user = User::factory()->create();
        
        // Create coupon
        $coupon = Coupon::factory()->create([
            'code' => 'REWARD10',
            'discount_amount' => 1000, // 10.00 with factor 100
            'discount_type' => \App\Enums\SaleTypeEnum::FIXED,
            'currency_factor' => 100,
        ]);
        
        // Create reward for user with coupon
        Reward::factory()->create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'is_active' => true,
        ]);
        
        // Authenticate user
        Sanctum::actingAs($user);
        
        // Make request
        $response = $this->getJson('/api/coupons');
        
        // Assert response
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => 'REWARD10'
        ]);
    }
    
    /** @test */
    public function it_returns_coupons_for_cart_stores()
    {
        // Create user
        $user = User::factory()->create();
        
        // Create store
        $store = Store::factory()->create();
        
        // Create coupon for store
        $coupon = Coupon::factory()->create([
            'code' => 'STORE20',
            'discount_amount' => 2000, // 20.00 with factor 100
            'discount_type' => \App\Enums\SaleTypeEnum::FIXED,
            'currency_factor' => 100,
        ]);
        
        // Attach coupon to store
        $coupon->stores()->attach($store->id);
        
        // Create cart with items from store
        $cart = Cart::factory()->create([
            'cart_key' => 'test-cart-key'
        ]);
        
        $product = Product::factory()->create([
            'store_id' => $store->id
        ]);
        
        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id
        ]);
        
        // Authenticate user
        Sanctum::actingAs($user);
        
        // Make request with cart key header
        $response = $this->withHeader('Cart-Key', 'test-cart-key')
                         ->getJson('/api/coupons');
        
        // Assert response
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'code' => 'STORE20'
        ]);
    }
    
    /** @test */
    public function it_returns_empty_when_no_reward_coupons()
    {
        // Create user without rewards
        $user = User::factory()->create();
        
        // Authenticate user
        Sanctum::actingAs($user);
        
        // Make request
        $response = $this->getJson('/api/coupons');
        
        // Assert response
        $response->assertStatus(200);
        $response->assertJson([
            'data' => []
        ]);
    }
}