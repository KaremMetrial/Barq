<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Promotion;
use App\Models\UserPromotionUsage;
use App\Services\PromotionEngineService;
use Modules\Store\Models\Store;
use Modules\User\Models\User;
use Modules\Cart\Models\Cart;
use App\Enums\PromotionTypeEnum;
use App\Enums\PromotionSubTypeEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PromotionEngineServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEngineService $promotionEngineService;
    protected Store $store;
    protected User $user;
    protected Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->promotionEngineService = app(PromotionEngineService::class);
        
        // إنشاء متجر
        $this->store = Store::factory()->create([
            'country_id' => 1,
            'city_id' => 1,
            'zone_id' => 1,
        ]);
        
        // إنشاء مستخدم
        $this->user = User::factory()->create();
        
        // إنشاء سلة
        $this->cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'user_id' => $this->user->id,
            'total' => 100000, // 1000 ريال
            'delivery_cost' => 2000, // 20 ريال
        ]);
    }

    public function test_free_delivery_promotion_evaluation(): void
    {
        // إنشاء ترقية توصيل مجاني
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::FREE_DELIVERY->value,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'min_order_amount' => 50000, // 50 ريال
            'country_id' => $this->store->country_id,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        $this->assertArrayHasKey('promotions', $result);
        $this->assertCount(1, $result['promotions']);
        
        $appliedPromotion = $result['promotions'][0];
        $this->assertTrue($appliedPromotion['is_valid']);
        $this->assertEquals('delivery', $appliedPromotion['type']);
        $this->assertEquals(2000, $appliedPromotion['savings']); // 20 ريال وفر
        $this->assertEquals(0, $appliedPromotion['new_delivery_cost']);
    }

    public function test_percentage_discount_promotion_evaluation(): void
    {
        // إنشاء ترقية خصم نسبة مئوية
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::PERCENTAGE_DISCOUNT->value,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'discount_value' => 50, // 50% خصم
            'min_order_amount' => 50000,
            'country_id' => $this->store->country_id,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        $this->assertCount(1, $result['promotions']);
        
        $appliedPromotion = $result['promotions'][0];
        $this->assertTrue($appliedPromotion['is_valid']);
        $this->assertEquals('delivery', $appliedPromotion['type']);
        $this->assertEquals(1000, $appliedPromotion['savings']); // 10 ريال وفر (50% من 20)
        $this->assertEquals(1000, $appliedPromotion['new_delivery_cost']); // 10 ريال جديد
    }

    public function test_first_order_promotion_evaluation(): void
    {
        // إنشاء ترقية للطلب الأول فقط
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::FIRST_ORDER->value,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'first_order_only' => true,
            'country_id' => $this->store->country_id,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        // المستخدم لديه طلبات سابقة، لذا لا يجب أن تكون الترقية صالحة
        $this->assertCount(0, $result['promotions']);
    }

    public function test_promotion_usage_limit(): void
    {
        // إنشاء ترقية بحد استخدام
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::FREE_DELIVERY->value,
            'is_active' => true,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'usage_limit' => 1,
            'usage_limit_per_user' => 1,
            'country_id' => $this->store->country_id,
        ]);

        // إنشاء استخدام سابق
        UserPromotionUsage::factory()->create([
            'promotion_id' => $promotion->id,
            'user_id' => $this->user->id,
            'usage_count' => 1,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        // لا يجب أن تكون الترقية صالحة بسبب تجاوز حد الاستخدام
        $this->assertCount(0, $result['promotions']);
    }

    public function test_inactive_promotion_not_evaluated(): void
    {
        // إنشاء ترقية غير نشطة
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::FREE_DELIVERY->value,
            'is_active' => false,
            'start_date' => now()->subDay(),
            'end_date' => now()->addDay(),
            'country_id' => $this->store->country_id,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        // لا يجب أن تكون الترقية مقيمة لأنها غير نشطة
        $this->assertCount(0, $result['promotions']);
    }

    public function test_promotion_outside_date_range_not_evaluated(): void
    {
        // إنشاء ترقية خارج نطاق التاريخ
        $promotion = Promotion::factory()->create([
            'type' => PromotionTypeEnum::DELIVERY->value,
            'sub_type' => PromotionSubTypeEnum::FREE_DELIVERY->value,
            'is_active' => true,
            'start_date' => now()->addDay(),
            'end_date' => now()->addDays(2),
            'country_id' => $this->store->country_id,
        ]);

        $result = $this->promotionEngineService->evaluatePromotions(
            $this->cart, 
            $this->store, 
            $this->user
        );

        // لا يجب أن تكون الترقية مقيمة لأنها خارج نطاق التاريخ
        $this->assertCount(0, $result['promotions']);
    }
}