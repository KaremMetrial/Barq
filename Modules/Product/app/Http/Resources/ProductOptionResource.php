<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\OptionInputTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductOptionResource extends JsonResource
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
            'product_id' => $this->product_id,
            'option_id' => $this->option_id,
            'min_select' => $this->min_select,
            'max_select' => $this->max_select,
            'is_required' => (bool) $this->is_required,
            'sort_order' => $this->sort_order,
            'option' => $this->whenLoaded('option', function () {
                return [
                    'id' => $this->option->id,
                    'name' => $this->option->name,
                    'input_type' => $this->option->input_type->value,
                    'input_type_label' => OptionInputTypeEnum::label($this->option->input_type->value),
                    'is_food_option' => (bool) $this->option->is_food_option,
                ];
            }),
            'option_values' => ProductOptionValueResource::collection($this->whenLoaded('optionValues')),
            // 'values' => ProductValueResource::collection($this->whenLoaded('values')),
        ];
    }
}
