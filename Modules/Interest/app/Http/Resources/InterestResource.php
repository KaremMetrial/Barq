<?php

namespace Modules\Interest\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "slug"=> $this->slug,
            "is_active"=> (bool) $this->is_active,
            "icon"=> asset('storage/'.$this->icon),
        ];
    }
}
