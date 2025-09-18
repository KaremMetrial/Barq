<?php

namespace Modules\Governorate\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Country\Http\Resources\CountryResource;

class GovernorateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "country" => new CountryResource($this->whenLoaded('country')),
            "is_active" => (bool) $this->is_active,
        ];
    }
}
