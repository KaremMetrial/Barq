<?php
namespace Modules\Search\Repositories;

use App\Enums\StoreStatusEnum;
use Modules\Product\Models\Product;
use Modules\Search\Repositories\Contracts\SearchRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

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
                        'logo' => $store->logo ? asset('storage/' . $store->logo) : null,
                        "status" => $store->status->value,
                        "status_label" => StoreStatusEnum::label($store->status->value),
                        "delivery_fee" => $store->getDeliveryFee() ?? 0,
                        'delivery_time_min' => $store->storeSetting->delivery_time_min,
                        'delivery_time_max' => $store->storeSetting->delivery_time_max,
                        'products'   => $storeProducts->map(fn($product) => [
                            'product_id'   => $product->id,
                            'product_name' => $product->name,
                            'product_image' => $product->images()->first()?->image_path ? asset('storage/' . $product->images()->first()?->image_path) : null,
                            'product_price' => number_format($product->price->price, 0),
                            'product_discount' => $product->offers->isNotEmpty() ? (string) $this->calculateSalePrice(
                                $product->price->price,
                                $product->offers->first()->discount_amount,
                                $product->offers->first()->discount_type->value
                            ) : null,
                            // 'has_offer' => $product->offers->isNotEmpty(),
                            'discount_type' => $product->offers->isNotEmpty() ? $product->offers->first()->discount_type->value : null,
                            'discount_value' => $product->offers->isNotEmpty() ? number_format($product->offers->first()->discount_amount, 0) : null,
                            'symbol_currency' => $product->store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'
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
    protected function calculateSalePrice($originalPrice, $discountAmount, $discountType)
    {
        if ($discountType === \App\Enums\SaleTypeEnum::PERCENTAGE->value) {
            return round($originalPrice - ($originalPrice * $discountAmount / 100), 2);
        }

        if ($discountType === \App\Enums\SaleTypeEnum::FIXED->value) {
            return round(max($originalPrice - $discountAmount, 0), 2);
        }

        return number_format($originalPrice, 0);
    }
    private function getProductsWithSection(string $search)
    {
        return Product::query()
            ->with([
                'store' => fn ($query) => $query->with('storeSetting', 'section.translations', 'address.zone.city.governorate.country'),
                'category' => fn ($query) => $query,
                'images',
                'price',
                'offers' => fn ($query) => $query->where('is_active', true)
                    ->where('status', \App\Enums\OfferStatusEnum::ACTIVE->value)
                    ->where(function ($q) {
                        $q->whereNull('start_date')
                            ->orWhere('start_date', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', now());
                    })
                    ->latest('id'),
            ])
            ->whereHas('translations', fn (Builder $query) => $query->where('name', 'like', "%$search%"))
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
