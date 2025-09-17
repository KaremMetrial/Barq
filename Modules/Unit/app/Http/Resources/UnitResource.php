<?php

namespace Modules\Unit\Http\Resources;

use App\Enums\UnitTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UnitResource extends JsonResource
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
            "abbreviation"=> $this->abbreviation,
            "type" => $this->type->value,
            "type_label" => UnitTypeEnum::label($this->type->value),
            "is_base"   => (bool) $this->is_base,
            "conversion_to_base" => $this->conversion_to_base,
        ];
    }
}
