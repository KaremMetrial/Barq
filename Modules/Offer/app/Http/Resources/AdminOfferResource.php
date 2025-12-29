<?php

namespace Modules\Offer\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\OfferTypeEnum;
use Illuminate\Http\Request;
use App\Enums\OfferStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;

class AdminOfferResource extends JsonResource
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
            "discount_amount" => (int) $this->discount_amount,
            "start_date" => $this->start_date,
            "end_date"=> $this->end_date,
            "is_flash_sale" => (bool) $this->is_flash_sale,
            "has_stock_limit" => (bool) $this->has_stock_limit,
            "stock_limit" => $this->stock_limit,
            "is_active" => (bool) $this->is_active,
            "status" => $this->status->value,
            "status_label" => OfferStatusEnum::label($this->status->value),
            "currency_code" => $this->currency_code,
            "currency_factor" => $this->currency_factor,
            "offerable_type" => $this->offerable_type,
            "offerable_id" => $this->offerable_id,
            "offerable" => $this->whenLoaded('offerable', function () {
                return match ($this->offerable_type) {
                    'product' => new ProductResource($this->offerable->load(['price','store', 'offers'])),
                    default => null,
                };
            }),
        ];
    }
}
