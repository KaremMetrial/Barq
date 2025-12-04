<?php

namespace Modules\Reward\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Country\Http\Resources\CountryResource;
use Modules\Coupon\Http\Resources\CouponResource;
use App\Enums\RewardType;

class RewardResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'type' => $this->type->value,
            'type_label' => RewardType::label($this->type->value),
            'points_cost' => (int) $this->points_cost,
            'value_amount' => (string) $this->value_amount,
            'is_active' => (bool) $this->is_active,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'usage_count' => $this->usage_count,
            'max_redemptions_per_user' => $this->max_redemptions_per_user,
            'total_redemptions' => (int) $this->total_redemptions,
            'remaining_redemptions' => $this->usage_count ? ($this->usage_count - $this->total_redemptions) : null,
            'country' => new CountryResource($this->whenLoaded('country')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
            'can_redeem' => $this->when(
                auth('sanctum')->check(),
                fn() => $this->isActive() && !$this->hasReachedLimit() && $this->canUserRedeem(auth('sanctum')->id())
            ),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
