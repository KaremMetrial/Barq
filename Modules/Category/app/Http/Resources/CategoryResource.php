<?php

namespace Modules\Category\Http\Resources;

use App\Enums\CategoryTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;

class CategoryResource extends JsonResource
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
            'subcategories' => CategoryResource::collection($this->whenLoaded('children')),
            'sections' => SectionResource::collection($this->whenLoaded('sections')),
        ];
    }
}
