<?php

namespace Modules\LoyaltySetting\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Country\Http\Resources\CountryResource;

class LoyaltySettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "earn_rate" => (string) $this->earn_rate,
            "min_order_for_earn" => (string) $this->min_order_for_earn,
            "referral_points" => (string) $this->referral_points,
            "rating_points" => (string) $this->rating_points,
            "country" => new CountryResource($this->country),
        ];
    }
}
