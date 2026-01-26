<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Promotion\Models\Promotion;
use Modules\Promotion\Models\PromotionTarget;
use Modules\Promotion\Models\PromotionFixedPrice;
use Modules\Store\Models\Store;
use Modules\Product\Models\Product;
use Modules\Category\Models\Category;
use Modules\User\Models\User;
use Modules\Country\Models\Country;
use Modules\City\Models\City;
use Modules\Zone\Models\Zone;

class PromotionApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    protected function createTestData(): void
    {
        // Create countries, cities, zones
        $this->country = Country::factory()->create(['name' => 'Test Country']);
        $this->city = City::factory()->create(['name' => 'Test City', 'country_id' => $this->country->id]);
        $this->zone = Zone::factory()->create(['name' => 'Test Zone', 'city_id' => $this->city->id]);

        // Create user
        $this->user = User::factory()->create(['status' => 'active']);

        // Create store
        $this->store = Store::factory()->create([
            'name' => 'Test Store',
            'country_id' => $this->country->id,
            'city_id' => $this->city->id,
            'zone_id' => $this->zone->id,
        ]);

        // Create category
        $this->category = Category::factory()->create(['name' => 'Test Category']);

        // Create products
        $this->product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'price' => 10000, // 100.00 in minor units
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Test Product 2',
            'store_id' => $this->store->id,
            'category_id' => $this->category->id,
            'price' => 20000, // 200.00 in minor units
        ]);
    }

    public function test_get_promotion_types()
    {
        $response = $this->getJson('/api/v1/admin/promotions/types');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'promotion_types' => [
                        '*' => [
                            'type',
                            'sub_type',
                            'label',
                            'description',
                            'fields'
                        ]
                    ]
                ],
                'message'
            ]);

        $promotionTypes = $response->json('data.promotion_types');
        $this->assertNotEmpty($promotionTypes);
    }

    public function test_get_promotion_types_by_type()
    {
        $response = $this->getJson('/api/v1/admin/promotions/types/delivery');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'type',
                    'promotion_types' => [
                        '*' => [
                            'sub_type',
                            'label',
                            'description',
                            'fields'
                        ]
                    ]
                ],
                'message'
            ]);

        $promotionTypes = $response->json('data.promotion_types');
        $this->assertNotEmpty($promotionTypes);
    }

    public function test_get_promotion_types_by_invalid_type()
    {
        $response = $this->getJson('/api/v1/admin/promotions/types/invalid');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'message.promotion_type_not_found'
            ]);
    }

    public function test_create_promotion_with_validation()
    {
        $promotionData = [
            'type' => 'delivery',
            'sub_type' => 'free_delivery',
            'is_active' => true,
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'min_order_amount' => 5000, // 50.00
            'currency_factor' => 100,
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Free Delivery Promotion',
                    'description' => 'Free delivery for orders over 50.00'
                ]
            ],
            'targets' => [
                [
                    'target_type' => 'store',
                    'target_id' => $this->store->id,
                    'is_excluded' => false
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/admin/promotions', $promotionData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'promotion' => [
                        'id',
                        'type',
                        'sub_type',
                        'is_active',
                        'start_date',
                        'end_date',
                        'min_order_amount',
                        'translations',
                        'targets'
                    ]
                ],
                'message'
            ]);

        $this->assertDatabaseHas('promotions', [
            'type' => 'delivery',
            'sub_type' => 'free_delivery',
            'is_active' => true,
        ]);
    }

    public function test_create_promotion_validation_errors()
    {
        $promotionData = [
            'type' => 'invalid_type',
            'sub_type' => 'invalid_sub_type',
            'start_date' => 'invalid_date',
            'end_date' => 'invalid_date',
            'min_order_amount' => -100,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/admin/promotions', $promotionData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);

        $errors = $response->json('errors');
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('sub_type', $errors);
        $this->assertArrayHasKey('start_date', $errors);
        $this->assertArrayHasKey('end_date', $errors);
        $this->assertArrayHasKey('min_order_amount', $errors);
    }

    public function test_create_product_promotion_with_fixed_prices()
    {
        $promotionData = [
            'type' => 'product',
            'sub_type' => 'fixed_price',
            'is_active' => true,
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'currency_factor' => 100,
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Fixed Price Promotion',
                    'description' => 'Fixed price for selected products'
                ]
            ],
            'fixed_prices' => [
                [
                    'store_id' => $this->store->id,
                    'product_id' => $this->product1->id,
                    'fixed_price' => 8000 // 80.00
                ],
                [
                    'store_id' => $this->store->id,
                    'product_id' => $this->product2->id,
                    'fixed_price' => 15000 // 150.00
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/admin/promotions', $promotionData);

        $response->assertStatus(200);

        $promotion = Promotion::latest()->first();
        $this->assertNotNull($promotion);
        $this->assertCount(2, $promotion->fixedPrices);
    }

    public function test_update_promotion()
    {
        $promotion = Promotion::factory()->create([
            'type' => 'delivery',
            'sub_type' => 'free_delivery',
            'is_active' => true,
            'min_order_amount' => 5000,
        ]);

        $updateData = [
            'is_active' => false,
            'min_order_amount' => 7500,
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Updated Promotion',
                    'description' => 'Updated promotion description'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->putJson("/api/v1/admin/promotions/{$promotion->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'promotion' => [
                        'id' => $promotion->id,
                        'is_active' => false,
                        'min_order_amount' => 7500
                    ]
                ]
            ]);

        $this->assertDatabaseHas('promotions', [
            'id' => $promotion->id,
            'is_active' => false,
            'min_order_amount' => 7500,
        ]);
    }

    public function test_delete_promotion()
    {
        $promotion = Promotion::factory()->create();

        $response = $this->actingAs($this->user, 'api')
            ->deleteJson("/api/v1/admin/promotions/{$promotion->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'message.success'
            ]);

        $this->assertDatabaseMissing('promotions', ['id' => $promotion->id]);
    }

    public function test_promotion_validation_endpoint()
    {
        $promotionData = [
            'type' => 'delivery',
            'sub_type' => 'free_delivery',
            'is_active' => true,
            'start_date' => now()->format('Y-m-d H:i:s'),
            'end_date' => now()->addDays(7)->format('Y-m-d H:i:s'),
            'min_order_amount' => 5000,
            'currency_factor' => 100,
            'translations' => [
                [
                    'locale' => 'en',
                    'title' => 'Test Promotion',
                    'description' => 'Test promotion description'
                ]
            ]
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/admin/promotions/validate', $promotionData);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_valid' => true,
                    'message' => 'message.promotion_validation_success'
                ]
            ]);
    }

    public function test_promotion_validation_endpoint_with_errors()
    {
        $promotionData = [
            'type' => 'invalid_type',
            'sub_type' => 'invalid_sub_type',
            'min_order_amount' => -100,
        ];

        $response = $this->actingAs($this->user, 'api')
            ->postJson('/api/v1/admin/promotions/validate', $promotionData);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'data' => [
                    'is_valid' => false,
                    'message' => 'message.promotion_validation_failed'
                ]
            ]);
    }

    public function test_promotion_validation_with_context()
    {
        $promotion = Promotion::factory()->create([
            'type' => 'delivery',
            'sub_type' => 'free_delivery',
            'is_active' => true,
            'country_id' => $this->country->id,
            'min_order_amount' => 5000,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->getJson("/api/v1/admin/promotions/{$promotion->id}/validate");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'is_valid',
                    'promotion',
                    'errors'
                ],
                'message'
            ]);
    }
}