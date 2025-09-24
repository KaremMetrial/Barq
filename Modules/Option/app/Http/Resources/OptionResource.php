<?php

namespace Modules\Option\Http\Resources;

use App\Enums\OptionInputTypeEnum;
use App\Enums\SaleTypeEnum;
use App\Enums\OptionTypeEnum;
use Illuminate\Http\Request;
use App\Enums\OptionStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
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
           "input_type"=> $this->input_type->value,
           "input_type_label"=> OptionInputTypeEnum::label($this->input_type->value),
           "is_food_option"=> (bool) $this->is_food_option,
        ];
    }
}
