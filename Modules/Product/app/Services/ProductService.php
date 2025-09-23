<?php

namespace Modules\Product\Services;

use App\Traits\FileUploadTrait;
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

    public function getAllProducts(): LengthAwarePaginator
    {
        return $this->ProductRepository->paginate(
            15,
            [
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
        return DB::transaction(function () use ($data, $id) {
            $product = $this->ProductRepository->update($id, $data['product']);

            $this->syncPharmacyInfo($product, $data['pharmacyInfo'] ?? []);
            $this->syncProductAllergen($product, $data['productAllergen'] ?? []);
            $this->syncAvailability($product, $data['availability'] ?? []);
            $this->syncNutrition($product, $data['productNutrition'] ?? []);
            $this->syncPrice($product, $data['prices'] ?? []);
            $this->syncTags($product, $data['tags'] ?? []);
            $this->syncUnits($product, $data['units'] ?? []);

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
}
