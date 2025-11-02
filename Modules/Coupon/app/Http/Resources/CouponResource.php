<?php

namespace Modules\Coupon\Http\Resources;

use App\Enums\SaleTypeEnum;
use Illuminate\Http\Request;
use App\Enums\CouponTypeEnum;
use App\Enums\ObjectTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;
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
            'discount_amount' => number_format($this->discount_amount,0),
            'discount_type' => $this->discount_type->value,
            'discount_type_label' => SaleTypeEnum::label($this->discount_type->value),
            'usage_limit' => (int) $this->usage_limit,
            'usage_limit_per_user' => (int) $this->usage_limit_per_user,
            'minimum_order_amount' => $this->minimum_order_amount,
            'start_date' => $this->start_date?->format('Y-m-d H:i:s'),
            'end_date' => $this->end_date?->diffForHumans(),
            'is_active' => (bool) $this->is_active,
            'coupon_type' => $this->coupon_type->value,
            'coupon_type_label' => CouponTypeEnum::label($this->coupon_type->value),
            'object_type' => $this->object_type->value,
            'object_type_label' => ObjectTypeEnum::label($this->object_type->value),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'symbol_currency' => 'EGP',
        ];
    }
}
