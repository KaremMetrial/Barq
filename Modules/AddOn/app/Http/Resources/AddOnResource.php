<?php

namespace Modules\AddOn\Http\Resources;

use App\Enums\AddOnApplicableToEnum;
use App\Enums\AddOnTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddOnResource extends JsonResource
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
            "description"=> $this->description,
            "price"=> $this->price,
            "is_active"=> (bool) $this->is_active,
            "applicable_to"=> $this->applicable_to->value,
            "applicable_to_label"=> AddOnApplicableToEnum::label($this->applicable_to->value),
        ];
    }
}
