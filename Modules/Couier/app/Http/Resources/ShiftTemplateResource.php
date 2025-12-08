<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'store_id' => $this->store_id,
            'is_active' => (bool) $this->is_active,
            'is_flexible' => (bool) $this->is_flexible,
            'days' => ShiftTemplateDayResource::collection($this->whenLoaded('days')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
