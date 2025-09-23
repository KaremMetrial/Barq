<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAvailabilityResource extends JsonResource
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
            "stock_quantity" => (int) $this->stock_quantity,
            "is_in_stock" => (bool) $this->is_in_stock,
            "available_start_date" => $this->available_start_date?->format('Y-m-d'),
            "available_end_date" => $this->available_end_date?->format('Y-m-d'),
        ];
    }
}
