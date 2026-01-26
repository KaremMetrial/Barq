<?php

namespace Modules\Promotion\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'sub_type' => $this->sub_type,
            'is_active' => $this->is_active,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_user' => $this->usage_limit_per_user,
            'current_usage' => $this->current_usage,
            'min_order_amount' => $this->min_order_amount,
            'max_order_amount' => $this->max_order_amount,
            'discount_value' => $this->discount_value,
            'fixed_delivery_price' => $this->fixed_delivery_price,
            'currency_factor' => $this->currency_factor,
            'first_order_only' => $this->first_order_only,
            'country_id' => $this->country_id,
            'city_id' => $this->city_id,
            'zone_id' => $this->zone_id,
            'targets' => PromotionTargetResource::collection($this->promotionTargets),
            'fixed_prices' => PromotionFixedPriceResource::collection($this->promotionFixedPrices),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

class PromotionTargetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'is_excluded' => $this->is_excluded,
        ];
    }
}

class PromotionFixedPriceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,
            'fixed_price' => $this->fixed_price,
        ];
    }
}