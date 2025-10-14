<?php

namespace Modules\Section\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\SectionTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Category\Http\Resources\CategoryResource;

class SectionResource extends JsonResource
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
            "slug"=> $this->slug,
            "icon" => $this->icon ? asset("storage/". $this->icon) : null,
            "is_active" => (bool) $this->is_active,
            "sort_order" => (int) $this->sort_order,
            "is_featured" => (bool) $this->is_featured,
            'categories' => CategoryResource::collection($this->categories)
        ];
    }
}
