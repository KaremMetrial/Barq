<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Store\Models\Store;
use Modules\Category\Models\Category;
use Modules\Product\Services\ProductService;
use Modules\Offer\Services\OfferService;
use App\Enums\SaleTypeEnum;

class OfferFormattingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_resource_sale_price_for_fixed_offer_uses_minor_units()
    {
        $store = Store::create([
            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
        ]);

        $category = Category::create([]);

        $productService = app(ProductService::class);

        $product = $productService->createProduct([
            'product' => [
                'store_id' => $store->id,
                'category_id' => $category->id,
                'barcode' => '111222333',
            ],
            'prices' => [
                'price' => 200.00,
                'purchase_price' => 100.00,
            ],
        ]);

        $offerService = app(OfferService::class);

        $offer = $offerService->createOffer([
            'discount_type' => SaleTypeEnum::FIXED->value,
            'discount_amount' => 25.00,
            'offerable_type' => \Modules\Product\Models\Product::class,
            'offerable_id' => $product->id,
            'is_active' => true,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'currency_factor' => 100,
        ]);

        $resource = new \Modules\Product\Http\Resources\ProductResource($product->fresh());
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('nearest_offer', $array);
        $this->assertEquals('175', $array['nearest_offer']['sale_price']); // 200 - 25 = 175
    }

    public function test_store_banner_text_for_fixed_offer_uses_minor_units()
    {
        $store = Store::create([
            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
        ]);

        $category = Category::create([]);

        $productService = app(ProductService::class);

        $product = $productService->createProduct([
            'product' => [
                'store_id' => $store->id,
                'category_id' => $category->id,
                'barcode' => '444555666',
            ],
            'prices' => [
                'price' => 164.00,
                'purchase_price' => 50.00,
            ],
        ]);

        $offerService = app(OfferService::class);

        $offer = $offerService->createOffer([
            'discount_type' => SaleTypeEnum::FIXED->value,
            'discount_amount' => 10.00,
            'offerable_type' => \Modules\Store\Models\Store::class,
            'offerable_id' => $store->id,
            'is_active' => true,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'currency_factor' => 100,
        ]);

        $resource = new \Modules\Store\Http\Resources\StoreResource($store->fresh());
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('active_sale', $array);
        $this->assertNotEmpty($array['active_sale']);
        $this->assertStringContainsString('10', $array['active_sale'][0]['discount_amount']);
    }
}
