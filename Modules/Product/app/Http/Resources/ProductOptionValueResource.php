<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionValueResource extends JsonResource
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
            'product_value_id' => $this->product_value_id,
            'product_option_id' => $this->product_option_id,
            'stock' => $this->stock,
            'is_default' => (bool) $this->is_default,
            'price' => (int) $this->price,
            'product_value' => $this->whenLoaded('productValue', function () {
                return [
                    'id' => $this->productValue->id,
                    'name' => $this->productValue->name,
                    'option_id' => $this->productValue->option_id,
                ];
            }),
        ];
    }
}
