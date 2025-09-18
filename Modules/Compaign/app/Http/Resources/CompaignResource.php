<?php

namespace Modules\Compaign\Http\Resources;

use App\Enums\SectionTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompaignResource extends JsonResource
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
            "description" => $this->description,
            "slug"=> $this->slug,
            "start_date" => $this->start_date?->format('Y-m-d H:i:s'),
            "end_date" => $this->end_date?->format('Y-m-d H:i:s'),
            "is_active" => (bool) $this->is_active,
        ];
    }
}
