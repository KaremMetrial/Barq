<?php

namespace Modules\ShippingPrice\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\ShippingPriceTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Category\Http\Resources\CategoryResource;

class ShippingPriceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "base_price"=> $this->base_price,
            "max_price"=> $this->max_price,
            "per_km_price"=> $this->per_km_price,
            "max_cod_price"=> $this->max_cod_price,
            "enable_cod"=> (bool) $this->enable_cod,
            "zone_id"=> $this->zone_id,
            "vehicle_id"=> $this->vehicle_id,
            "created_at"=> $this->created_at,
            "updated_at"=> $this->updated_at,
            "is_active" => $this->is_active,
            "currency_factor" => $this->zone->getCurrencyFactor(),
        ];
    }
}
