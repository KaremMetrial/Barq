<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\ProductStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Tag\Http\Resources\TagResource;
use Modules\Unit\Http\Resources\UnitResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"         => $this->id,
            "name"       => $this->name,
            "description" => $this->description,
            "is_active"  => (bool) $this->is_active,
            "max_cart_quantity" => (int) $this->max_cart_quantity,
            "status"     => $this->status?->value,
            "status_label" => ProductStatusEnum::label($this->status->value),
            "note"       => $this->note,
            "is_reviewed" => (bool) $this->is_reviewed,
            "is_vegetarian" => (bool) $this->is_vegetarian,
            "is_featured" => (bool) $this->is_featured,
            "barcode" => $this->barcode,
            "images" => ProductImageResource::collection($this->whenLoaded("images")),
            "price" => $this->whenLoaded('price', function () {
                return [
                    "price" => $this->price->price,
                    "purchase_price" => $this->price->purchase_price,
                ];
            }),
            "store"      => $this->whenLoaded('store', function () {
                return [
                    "id"   => $this->store->id,
                    "name" => $this->store->name,
                ];
            }),
            "category"   => $this->whenLoaded('category', function () {
                return [
                    "id" => $this->category->id,
                    "name" => $this->category->name,
                ];
            }),
            "availability" => new ProductAvailabilityResource($this->whenLoaded("availability")),
            "tags" => TagResource::collection($this->whenLoaded("tags")),
            'units' => $this->whenLoaded('units', function () {
                return $this->units->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'unit_value' => $unit->pivot?->unit_value,
                    ];
                });
            }),
            "productNutrition" => new ProductNutritionResource($this->whenLoaded("ProductNutrition")),
            "productAllergen" => ProductAllergenResource::collection($this->whenLoaded("productAllergen")),
            "pharmacyInfo" => PharmacyInfoResource::collection($this->whenLoaded("pharmacyInfo")),
            "watermarks" => new ProductWatermarksResource($this->whenLoaded("watermark")),
        ];
    }
}
