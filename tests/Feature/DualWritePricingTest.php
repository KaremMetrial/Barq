<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Store\Models\Store;
use Modules\Category\Models\Category;
use Modules\Product\Services\ProductService;
use Modules\Product\Models\Product;
use App\Helpers\CurrencyHelper;
use Modules\Offer\Services\OfferService;
use App\Enums\SaleTypeEnum;

class DualWritePricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_service_writes_minor_units_on_create()
    {
        // Create a store with currency info
        $store = Store::create([
            'currency_code' => 'EGP',
            'currency_symbol' => 'ج.م',
        ]);

        $category = Category::create([]);

        $productService = app(ProductService::class);

        $data = [
            'product' => [
                'store_id' => $store->id,
                'category_id' => $category->id,
                'barcode' => '123456789',
            ],
            'prices' => [
                'price' => 278.00,
                'purchase_price' => 100.00,
            ],
        ];

        $product = $productService->createProduct($data);

        $this->assertNotNull($product->price);
        $this->assertEquals(27800, $product->price->price_minor);
        $this->assertEquals(10000, $product->price->purchase_price_minor);
    }

    public function test_offer_service_creates_discount_amount_minor_on_fixed()
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
                'barcode' => '987654321',
            ],
            'prices' => [
                'price' => 164.00,
                'purchase_price' => 50.00,
            ],
        ]);

        $offerService = app(OfferService::class);

        $offerData = [
            'discount_type' => SaleTypeEnum::FIXED->value,
            'discount_amount' => 25.00,
            'offerable_type' => Product::class,
            'offerable_id' => $product->id,
            'is_active' => true,
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'currency_factor' => 100,
        ];

        $offer = $offerService->createOffer($offerData);

        $this->assertNotNull($offer);
        $this->assertEquals(2500, $offer->discount_amount_minor);
        $this->assertEquals(100, $offer->currency_factor);
    }
}
