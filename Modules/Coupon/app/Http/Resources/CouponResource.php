<?php

namespace Modules\Coupon\Http\Resources;

use App\Enums\SaleTypeEnum;
use Illuminate\Http\Request;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Modules\Category\Models\Category;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\Governorate\Http\Resources\GovernorateResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_amount' => (int) $this->discount_amount,
            'discount_type' => $this->discount_type->value,
            'discount_type_label' => SaleTypeEnum::label($this->discount_type->value),
            'usage_limit' => (int) $this->usage_limit,
            'usage_limit_per_user' => (int) $this->usage_limit_per_user,
            'minimum_order_amount' => (int) $this->minimum_order_amount,
            'max_order_amount' => (int) $this->maximum_order_amount,
            'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date?->format('Y-m-d H:i:s'),
            'is_active' => (bool) $this->is_active,
            'coupon_type' => $this->coupon_type->value,
            'coupon_type_label' => CouponTypeEnum::label($this->coupon_type->value),
            'object_type' => $this->object_type->value,
            'object_type_label' => ObjectTypeEnum::label($this->object_type->value),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'symbol_currency' => $this->getCurrencySymbol(),
            'used_count' => (int) $this->usageCount(),
            'currency_factor' => $this->getCurrencyFactor(),
            'categories' => $this->whenLoaded('categories',function() {
                return CategoryResource::collection($this->categories);
            }),
            'products' => $this->whenLoaded('products',function() {
                return ProductResource::collection($this->products);
            }),
            'stores' => $this->whenLoaded('stores',function() {
                return StoreResource::collection($this->stores);
            }),

        ];
    }
}
