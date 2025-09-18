<?php

namespace Modules\Zone\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\City\Http\Resources\CityResource;

class ZoneResource extends JsonResource
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
            "area"=> $this->area,
            "is_active" => (bool) $this->is_active,
            "city" => new CityResource($this->whenLoaded("city")),
        ];
    }
}
