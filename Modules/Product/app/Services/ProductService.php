<?php

namespace Modules\Product\Services;

use Carbon\Carbon;
use App\Enums\SaleTypeEnum;
use Illuminate\Support\Arr;
use Modules\Zone\Models\Zone;
use App\Traits\FileUploadTrait;
use App\Enums\ProductStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Product\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Product\Repositories\ProductRepository;

class ProductService
{
    use FileUploadTrait;
    public function __construct(
        protected ProductRepository $ProductRepository
    ) {}

    public function getAllProducts(array $filters = []): LengthAwarePaginator
    {
        return $this->ProductRepository->paginate(
            $filters,
            5,
            $this->defaultProductRelations()
        );
    }
    private function generateUniqueBarcode(): string
    {
        $barcode = rand(100000000000, 999999999999);
        while (Product::where('barcode', $barcode)->exists()) {
            $barcode = rand(100000000000, 999999999999);
        }
        return (string) $barcode;
    }

    public function createProduct(array $data): ?Product
    {
        return DB::transaction(function () use ($data) {
            \Log::info('Creating product with data: ', $data);
            $data['availability']['store_id'] = $data['product']['store_id'];
            $data['product']['barcode'] = $data['product']['barcode'] ?? $this->generateUniqueBarcode();
            $product = $this->ProductRepository->create($data['product']);

            $this->syncPharmacyInfo($product, $data['pharmacyInfo'] ?? []);
            $this->syncProductAllergen($product, $data['productAllergen'] ?? []);
            $this->syncAvailability($product, $data['availability'] ?? []);
            $this->syncNutrition($product, $data['productNutrition'] ?? []);
            $this->syncPrice($product, $data['prices'] ?? []);
            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncProductOptions($product, $data['productOptions'] ?? []);
            $this->syncAddOns($product, $data['add_ons'] ?? []);

            if (!empty($data['images'])) {
                $this->handleProductImages($product, $data['images']);
            }

            if (!empty($data['watermarks'])) {
                $this->handleWatermark($product, $data['watermarks']);
            }

            return $product->refresh();
        });
    }

    public function getProductById(int $id): ?Product
    {
        return $this->ProductRepository->find($id, $this->defaultProductRelations());
    }


    public function updateProduct(int $id, array $data): ?Product
    {
        return DB::transaction(function () use ($data, $id) {
            $product = $this->ProductRepository->find($id);
            if (isset($data['product'])) {
                $product = $this->ProductRepository->update($id, $data['product']);
            }

            $this->syncPharmacyInfo($product, $data['pharmacyInfo'] ?? []);
            $this->syncProductAllergen($product, $data['productAllergen'] ?? []);
            $this->syncAvailability($product, $data['availability'] ?? []);
            $this->syncNutrition($product, $data['productNutrition'] ?? []);

            $this->syncPrice($product, $data['prices'] ?? []);

            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncUnits($product, $data['units'] ?? []);

            if (isset($data['productOptions'])) {
                $this->syncProductOptions($product, $data['productOptions']);
            }

            if (isset($data['add_ons'])) {
                $this->syncAddOns($product, $data['add_ons']);
            }

            if (isset($data['images'])) {
                $product->images()->delete();
                $this->handleProductImages($product, $data['images']);
            }

            if (isset($data['watermarks'])) {
                $product->watermark()->delete();
                $this->handleWatermark($product, $data['watermarks']);
            }

            return $product->refresh();
        });
    }

    public function deleteProduct(int $id): bool
    {
        return $this->ProductRepository->delete($id);
    }
    private function handleProductImages(Product $product, array $images): void
    {
        foreach ($images as $index => $image) {
            if (isset($image['image_path']) && request()->hasFile("images.$index.image_path")) {
                $imagePath = $this->upload(request(), "images.$index.image_path", 'products/images','public',[512,512]);

                $product->images()->create([
                    'image_path' => $imagePath,
                    'is_primary' => $image['is_primary'] ?? false,
                ]);
            }
        }
    }
    private function handleWatermark(Product $product, array $watermarkData): void
    {
        if (isset($watermarkData['image_url']) && request()->hasFile('watermarks.image_url')) {
            $watermarkFile = request()->file('watermarks.image_url');
            $watermarkImagePath = $this->upload(request(), 'watermarks.image_url', 'products/watermarks','public',[512,512]);

            $product->watermark()->create([
                'image_url' => $watermarkImagePath,
                'position' => $watermarkData['position'],
                'opacity' => $watermarkData['opacity'],
            ]);
        }
    }
    private function syncPharmacyInfo(Product $product, array $info): void
    {
        if (!empty($info)) {
            $product->pharmacyInfo()->delete();
            $product->pharmacyInfo()->createMany($info);
        }
    }

    private function syncProductAllergen(Product $product, array $allergens): void
    {
        if (!empty($allergens)) {
            $product->productAllergen()->delete();
            $product->productAllergen()->createMany($allergens);
        }
    }

    private function syncAvailability(Product $product, array $availability): void
    {
        if (!empty($availability)) {
            $product->availability()->updateOrCreate([], $availability);
        }
    }

    private function syncNutrition(Product $product, array $nutrition): void
    {
        if (!empty($nutrition)) {
            $product->productNutrition()->updateOrCreate([], $nutrition);
        }
    }

    private function syncPrice(Product $product, array $price): void
    {

        if (!empty($price)) {
            $price = array_filter($price, fn($value) => $value !== null);

            // Get currency information from the store (cached)
            $currencyInfo = \App\Helpers\CurrencyHelper::getCurrencyInfoFromStore(store: $product->store);
            // Use provided currency_factor or fallback to store's currency_factor or default to 100
            $factor = $price['currency_factor'] ?? $currencyInfo['currency_factor'] ?? 100;
            if (isset($price['price'])) {
                $price['price'] = \App\Helpers\CurrencyHelper::toMinorUnits($price['price'], $factor);
            }
            if (isset($price['purchase_price'])) {
                $price['purchase_price'] = \App\Helpers\CurrencyHelper::toMinorUnits($price['purchase_price'], $factor);
            }
            if (isset($price['sale_price'])) {
                $price['sale_price'] = \App\Helpers\CurrencyHelper::toMinorUnits($price['sale_price'], $factor);
            }
            $priceData = array_merge($price, [
                'currency_code' => $currencyInfo['currency_code'],
                'currency_symbol' => $currencyInfo['currency_symbol'],
                'currency_factor' => $factor,
            ]);
            $product->price()->updateOrCreate([], $priceData);
        }
    }

    private function syncTags(Product $product, array $tags): void
    {
        $product->tags()->sync($tags);
    }

    private function syncUnits(Product $product, array $units): void
    {
        if (!empty($units)) {
            $unitData = collect($units)->mapWithKeys(function ($unit) {
                return [$unit['unit_id'] => ['unit_value' => $unit['unit_value']]];
            })->toArray();

            $product->units()->sync($unitData);
        }
    }
    private function syncProductOptions(Product $product, array $options): void
    {
        if (empty($options))
            return;

        $product->productOptions()->delete();

        foreach ($options as $option) {
            $productOption = $product->productOptions()->create([
                'option_id' => $option['option_id'],
                'min_select' => $option['min_select'] ?? 0,
                'max_select' => $option['max_select'] ?? 1,
                'is_required' => $option['is_required'] ?? true,
                'sort_order' => $option['sort_order'] ?? 1,
            ]);

            if (!empty($option['values'])) {
                foreach ($option['values'] as $value) {
                    $productValue = $productOption->values()->create(['name' => $value['name']]);
                    $currencyInfo = \App\Helpers\CurrencyHelper::getCurrencyInfoFromStore(store: $product->store);
                    // Use provided currency_factor or fallback to store's currency_factor or default to 100
                    $factor = $value['currency_factor'] ?? $currencyInfo['currency_factor'] ?? 100;
                    \Log::info('currencyFactor', [$factor]);
                    $productValue->optionValues()->create([
                        'product_value_id' => $productValue->id,
                        'product_option_id' => $productOption->id,
                        'price' => $value['price'] ? \App\Helpers\CurrencyHelper::toMinorUnits($value['price'], $factor) : 0,
                        'stock' => $value['stock'] ?? 0,
                        'is_default' => $value['is_default'] ?? false,
                        'is_in_stock' => $value['is_in_stock'] ?? 0,
                    ]);
                }
            }
        }
    }
    public function getHomeProducts()
    {
        return $this->ProductRepository->home($this->defaultProductRelations());
    }

    public function getGroupedProductsByStore(int $storeId, array $filters = []): array
    {
        $perPage = Arr::get($filters, 'per_page', 10);

        $query = Product::with(['category', 'images', 'price', 'availability', 'offers'])
            ->where('store_id', $storeId)
            ->whereStatus(ProductStatusEnum::ACTIVE)
            ->latest();

        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (!empty($filters['search'])) {
            $query->whereTranslationLike('name', '%' . $filters['search'] . '%');
        }

        $paginator = $query->paginate($perPage);
        $products = $paginator->getCollection();

        $groupedByCategory = $products->groupBy(function ($product) {
            return $product->category?->name ?? 'Uncategorized';
        });

        $grouped = [];

        foreach ($groupedByCategory as $categoryName => $items) {
            $grouped[$categoryName] = $items->values();
        }

        return [
            'grouped_products' => $grouped,
            'paginator' => $paginator,
        ];
    }

    public function getProductsWithOffersEndingSoon(array $filters = []): array
    {
        $days = Arr::get($filters, 'days', 2);
        $perPage = Arr::get($filters, 'per_page', 15);
        $storeId = Arr::get($filters, 'store_id');
        $page = Arr::get($filters, 'page', 1);
        $categoryId = Arr::get($filters, 'category_id');


        $addressId = request()->header('address-id') ?? request()->header('AddressId');
        $lat = request()->header('lat');
        $lng = request()->header('lng');

        $zone = null;

        if ($addressId) {
            $zone = Zone::findZoneByAddressId($addressId);
        } elseif ($lat && $lng) {
            $zone = Zone::findZoneByCoordinates($lat, $lng);
        }

        $sectionId = $filters['section_id'] ?? 0;
        if (array_key_exists('section_id', $filters) && (int)$filters['section_id'] == 0) {
            $latestSection = \Modules\Section\Models\Section::where('type', '!=', 'delivery_company')->latest()->first();
            $sectionId = $latestSection?->id;
        }

        $now = now();
        $endDateThreshold = $now->copy()->addDays($days)->endOfDay();

        $offerConstraint = function ($query) use ($now, $endDateThreshold) {
            return $query->where('is_active', true)
                ->where('status', \App\Enums\OfferStatusEnum::ACTIVE->value)
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now)
                ->where('end_date', '<=', $endDateThreshold)
                ->orderBy('end_date', 'asc');
        };

        $query = Product::with(array_merge(
            $this->defaultProductRelations(),
            ['offers' => $offerConstraint]
        ))
            ->whereHas('offers', $offerConstraint)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->when($sectionId, fn($q) => $q->whereHas('store', fn($q2) => $q2->where('section_id', $sectionId)))
            ->when($categoryId && $categoryId != 0, fn($q) => $q->where('category_id', $categoryId))
            ->where('is_active', true)
            ->where('status', ProductStatusEnum::ACTIVE->value)
            ->orderByRaw('(
        SELECT MIN(end_date) FROM offers
        WHERE offerable_id = products.id
        AND offerable_type = ?
        AND is_active = 1
        AND status = "active"
        AND end_date IS NOT NULL
        AND end_date >= ?
        AND end_date <= ?
    ) ASC', [
                Product::class,
                $now,
                $endDateThreshold
            ]);
                    if ($zone) {
            $query->whereHas('store', function ($q) use ($zone) {
                $q->whereHas('zoneToCover', function ($qz) use ($zone) {
                    $qz->where('zones.id', $zone->id);
                });
            });
        } else {
            if ($addressId || ($lat && $lng)) {
                $query->whereRaw('1 = 0');
            }
        }

        $products = $query->paginate($perPage, ['*'], 'page', $page);

        return [
            'products' => $products,
            'meta' => [
                'total_products' => $products->total(),
                'days_threshold' => (int) $days,
                'end_date_threshold' => $endDateThreshold->toDateTimeString(),
                'timeframe' => "Next {$days} days",
                'current_time' => $now->toDateTimeString(),
            ]
        ];
    }

    /**
     * Calculate sale price based on discount
     */
    private function calculateSalePrice(float $basePrice, float $discountAmount, string $discountType): float
    {
        if ($discountType == SaleTypeEnum::PERCENTAGE->value) {
            return $basePrice - ($basePrice * ($discountAmount / 100));
        }

        return max(0, $basePrice - $discountAmount);
    }
    private function defaultProductRelations(): array
    {
        return [
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
            'requiredOptions',
            'productOptions.option.translations',
            'productOptions.optionValues.productValue.translations',
            'addOns'
        ];
    }

    private function applyActiveOfferQuery($query)
    {
        return $query
            ->where('is_active', true)
            ->where('status', \App\Enums\OfferStatusEnum::ACTIVE->value)
            ->whereNotNull('end_date');
    }
    private function syncAddOns(Product $product, array $addOns): void
    {
        $product->addOns()->sync($addOns);
    }

    public function getStats(int $productId): array
    {
        return $this->ProductRepository->getStats($productId);
    }
    public function toggleActive(int $productId)
    {
        return $this->ProductRepository->toggleActive($productId);
    }
}
