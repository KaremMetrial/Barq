<?php

namespace Modules\Product\Services;

use Carbon\Carbon;
use App\Enums\SaleTypeEnum;
use Illuminate\Support\Arr;
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
            15,
            [
                'store.storeSetting',
                'category',
                'images',
                'price',
                'availability',
                'tags',
                'units',
                'ProductNutrition',
                'productAllergen',
                'pharmacyInfo',
                'watermark'
            ]
        );
    }

    public function createProduct(array $data): ?Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->ProductRepository->create($data['product']);

            $this->syncPharmacyInfo($product, $data['pharmacyInfo'] ?? []);
            $this->syncProductAllergen($product, $data['productAllergen'] ?? []);
            $this->syncAvailability($product, $data['availability'] ?? []);
            $this->syncNutrition($product, $data['productNutrition'] ?? []);
            $this->syncPrice($product, $data['prices'] ?? []);
            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncProductOptions($product, $data['productOptions'] ?? []);

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
        return $this->ProductRepository->find($id, [
            'store',
            'category',
            'images',
            'price',
            'availability',
            'tags',
            'units',
            'ProductNutrition',
            'productAllergen',
            'pharmacyInfo',
            'watermark'
        ]);
    }

    public function updateProduct(int $id, array $data): ?Product
    {
        dd($data);
        return DB::transaction(function () use ($data, $id) {
            $product = $this->ProductRepository->update($id, $data['product']);
            $this->syncPharmacyInfo($product, $data['pharmacyInfo'] ?? []);
            $this->syncProductAllergen($product, $data['productAllergen'] ?? []);
            $this->syncAvailability($product, $data['availability'] ?? []);
            $this->syncNutrition($product, $data['productNutrition'] ?? []);
            $this->syncPrice($product, $data['prices'] ?? []);
            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncUnits($product, $data['units'] ?? []);
            $this->syncProductOptions($product, $data['productOptions'] ?? []);

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
                $imagePath = $this->upload(request(), "images.$index.image_path", 'products/images');

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
            $watermarkImagePath = $this->upload(request(), 'watermarks.image_url', 'products/watermarks');

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
            $product->price()->updateOrCreate([], $price);
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
        foreach ($options as $option) {
            $productOption = $product->productOptions()->create([
                'option_id' => $option['option_id'],
                'min_select' => $option['min_select'] ?? 0,
                'max_select' => $option['max_select'] ?? 1,
                'is_required' => $option['is_required'] ?? false,
                'sort_order' => $option['sort_order'] ?? 1,
            ]);

            if (!empty($option['values'])) {
                // Create product values
                $productValues = $productOption->values()->createMany(
                    collect($option['values'])->map(fn($value) => ['name' => $value['name']])->toArray()
                );

                // Create option values for each product value
                foreach ($productValues as $productValue) {
                    foreach ($option['values'] as $value) {
                        $productValue->optionValues()->create([
                            'product_value_id' => $productValue->id,
                            'product_option_id' => $productOption->id,
                            'price' => $value['price'] ?? 0,
                            'stock' => $value['stock'] ?? 0,
                            'is_default' => $value['is_default'] ?? false,
                        ]);
                    }
                }
            }
        }
    }
    public function getHomeProducts()
    {
        $relation = ['store.storeSetting', 'category', 'images', 'price', 'availability'];
        return $this->ProductRepository->home($relation);
    }

    public function getGroupedProductsByStore(int $storeId, array $filters = []): array
    {
        $perPage = Arr::get($filters, 'per_page', 10);

        $query = Product::with(['category', 'images', 'price', 'availability'])
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

        $now = Carbon::now();
        $endDateThreshold = $now->copy()->addDays($days);

        $query = Product::with([
            'store.storeSetting',
            'category',
            'images',
            'price',
            'availability',
            'tags',
            'offers' => function ($query) use ($now, $endDateThreshold) {
                $query->where('is_active', true)
                    ->where('status', 'active')
                    ->whereNotNull('end_date')
                    ->where('end_date', '>=', $now->toDateString())
                    ->where('end_date', '<=', $endDateThreshold->toDateString())
                    ->orderBy('end_date', 'asc');
            }
        ])
        ->whereHas('offers', function ($query) use ($now, $endDateThreshold) {
            $query->where('is_active', true)
                ->where('status', 'active')
                ->whereNotNull('end_date')
                ->where('end_date', '>=', $now->toDateString())
                ->where('end_date', '<=', $endDateThreshold->toDateString());
        })
        ->when($storeId, function ($query, $storeId) {
            $query->where('store_id', $storeId);
        })
        ->where('is_active', true)
        ->where('status', ProductStatusEnum::ACTIVE)
        ->orderByRaw('(SELECT end_date FROM offers WHERE offerable_id = products.id AND offerable_type = ? AND is_active = 1 AND status = "active" ORDER BY end_date ASC LIMIT 1)', [Product::class]);
        $total = $query->count();

        $products = $query->paginate($perPage, ['*'], 'page', $page);
        $products->getCollection()->transform(function ($product) {
            $nearestOffer = $product->offers->sortBy('end_date')->first();
            $product->nearest_offer = $nearestOffer ? [
                'id' => $nearestOffer->id,
                'discount_type' => $nearestOffer->discount_type,
                'discount_amount' => $nearestOffer->discount_amount,
                'start_date' => $nearestOffer->start_date,
                'end_date' => $nearestOffer->end_date,
                'is_flash_sale' => $nearestOffer->is_flash_sale,
                'has_stock_limit' => $nearestOffer->has_stock_limit,
                'stock_limit' => $nearestOffer->stock_limit,
                'ends_in' => Carbon::parse($nearestOffer->end_date)->diffForHumans(),
                'sale_price' => $this->calculateSalePrice(
                    $product->price->first()->price ?? 0,
                    $nearestOffer->discount_amount,
                    $nearestOffer->discount_type->value
                )
            ] : null;

            return $product;
        });
        return [
            'products' => $products,
            'meta' => [
                'total_products' => $total,
                'days_threshold' => (int)$days,
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
}
