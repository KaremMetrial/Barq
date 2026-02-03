<?php

namespace Modules\Slider\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Store\Http\Resources\StoreResource;

class SliderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "type" => $this->target,
            "title" => $this->title,
            "body" => $this->body,
            "image" => $this->image ? asset('storage/' . $this->image) : null,
            "button_text" => $this->button_text,
            "target" => $this->target,
            "target_id" => $this->target_id,
            "is_active" => (bool) $this->is_active,
            "sort_order" => $this->sort_order,
        ];
    }
}