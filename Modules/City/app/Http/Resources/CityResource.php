<?php

namespace Modules\City\Http\Resources;

use App\Enums\SectionTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Governorate\Http\Resources\GovernorateResource;

class CityResource extends JsonResource
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
            "is_active" => (bool) $this->is_active,
            "governorate" => new GovernorateResource($this->whenLoaded('governorate'))
        ];
    }
}
