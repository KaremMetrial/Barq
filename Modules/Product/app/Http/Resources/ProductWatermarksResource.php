<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductWatermarksResource extends JsonResource
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
            "image_url" => $this->image_url ? asset('storage/' . $this->image_url) : null,
            "position" => $this->position,
            "opacity" => (int) $this->opacity,
        ];
    }
}
