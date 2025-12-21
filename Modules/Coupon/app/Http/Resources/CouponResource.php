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
            'discount_amount' => (int) $this->discount_amount,
            'formatted_discount_amount' => $this->getFormattedDiscountAmount(),
            'discount_type' => $this->discount_type->value,
            'discount_type_label' => SaleTypeEnum::label($this->discount_type->value),
            'usage_limit' => (int) $this->usage_limit,
            'usage_limit_per_user' => (int) $this->usage_limit_per_user,
            'minimum_order_amount' => $this->getFormattedMinimumOrderAmount(),
            'max_order_amount' => $this->getFormattedMaximumOrderAmount(),
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
            'used_count' => $this->getUsedCount(),
            'currency_factor' => $this->getCurrencyFactor(),
        ];
    }
    
    /**
     * Get currency symbol based on user's country or default
     *
     * @return string
     */
    private function getCurrencySymbol(): string
    {
        // Try to get currency symbol from authenticated user's country
        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            return $user->getCurrencySymbol();
        }
        
        // Default currency symbol
        return 'EGP';
    }
    
    /**
     * Get actual used count from the coupon's usage_count field
     *
     * @return int
     */
    private function getUsedCount(): int
    {
        return (int) ($this->usage_count ?? 0);
    }
}