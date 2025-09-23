<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductNutritionResource extends JsonResource
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
            "calories" => (int) $this->calories,
            "fat" => (int) $this->fat,
            "protein" => (int) $this->protein,
            "carbohydrates" => (int) $this->carbohydrates,
            "sugar" => (int) $this->sugar,
            "fiber" => (int) $this->fiber,
        ];
    }
}
