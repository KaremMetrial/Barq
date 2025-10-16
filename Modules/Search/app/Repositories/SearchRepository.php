<?php

namespace Modules\Search\Repositories;

use App\Enums\StoreStatusEnum;
use Modules\Product\Models\Product;
use Modules\Search\Repositories\Contracts\SearchRepositoryInterface;

class SearchRepository implements SearchRepositoryInterface
{
    public function autocomplete(string $search): array
    {
        $products = $this->getProductsWithSection($search);

        $sections = $this->groupProductsBySectionName($products);
        $sections->put('all', $products->pluck('name')->unique()->values());

        return ['sections' => $sections];
    }

    public function search(string $search): array
    {
        $products = $this->getProductsWithSection($search);

        $sectionGroups = $products->groupBy(fn($product) => optional($product->store->section)?->id)->filter();

        $structured = $sectionGroups->map(function ($sectionProducts) {
            $section = $sectionProducts->first()->store->section;

            $stores = $sectionProducts->groupBy(fn($product) => $product->store->id)
                ->map(function ($storeProducts) {
                    $store = $storeProducts->first()->store;

                    return [
                        'store_id'   => $store->id,
                        'store_name' => $store->name,
                        'note' => $store->note,
                        'is_closed' => (bool) $store->is_closed,
                        'avg_rate' => $store->avg_rate,
                        'logo' => $store->logo,
                        "status" => $store->status->value,
                        "status_label" => StoreStatusEnum::label($store->status->value),
                        "delivery_fee" => $store->getDeliveryFee() ?? 0,
                        'delivery_time_max' => $store->storeSetting->delivery_time_max,
                        'products'   => $storeProducts->map(fn($product) => [
                            'product_id'   => $product->id,
                            'product_name' => $product->name,
                            'product_image' => $product->images()->first()?->image_path,
                            'product_price' => $product->price->price,
                            'product_discount' => $product->price->sale?->price,
                        ])->values()
                    ];
                })->values();

            return [
                'section_id'   => $section->id,
                'section_name' => $section->name,
                'stores'       => $stores
            ];
        })->values();

        return ['sections' => $structured];
    }

    private function getProductsWithSection(string $search)
    {
        return Product::with([
            'store.translations',
            'store.storeSetting',
            'category.translations',
            'images',
            'price',
            'availability',
            'tags',
            'units.translations',
            'ProductNutrition',
            'productAllergen.translations',
            'pharmacyInfo.translations',
            'watermark',
            'offers',
            'store.section.translations',
        ])
            ->withTranslation()
            ->whereTranslationLike('name', "%$search%")
            ->whereHas('store.section')
            ->get();
    }

    private function groupProductsBySectionName($products)
    {
        return $products->groupBy(function ($product) {
            return optional($product->store->section)?->name ?? 'Uncategorized';
        })->mapWithKeys(function ($products, $sectionName) {
            return [
                $sectionName => $products->pluck('name')->unique()->values()
            ];
        });
    }
}
