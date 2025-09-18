<?php

namespace Modules\Offer\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\OfferTypeEnum;
use Illuminate\Http\Request;
use App\Enums\OfferStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
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
            "discount_type"=> $this->discount_type->value,
            "discount_type_label" => SaleTypeEnum::label($this->discount_type->value),
            "start_date" => $this->start_date?->format("Y-m-d H:i:s"),
            "end_date"=> $this->end_date?->format("Y-m-d H:i:s"),
            "is_flash_sale" => (bool) $this->is_flash_sale,
            "has_stock_limit" => (bool) $this->has_stock_limit,
            "stock_limit" => $this->stock_limit,
            "is_active" => (bool) $this->is_active,
            "status" => $this->status->value,
            "status_label" => OfferStatusEnum::label($this->status->value),
        ];
    }
}
