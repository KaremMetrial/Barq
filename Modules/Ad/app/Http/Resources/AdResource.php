<?php

namespace Modules\Ad\Http\Resources;

use App\Enums\AdStatusEnum;
use App\Enums\AdTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdResource extends JsonResource
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
            "title" => $this->title,
            "description" => $this->description,
            "type" => $this->type->value,
            "type_label" => AdTypeEnum::label($this->type->value),
            "is_active" => (bool) $this->is_active,
            "status" => $this->status->value,
            "status_label" => AdStatusEnum::label($this->status->value),
            "start_date" => $this->start_date?->format('Y-m-d'),
            "end_date" => $this->end_date?->format('Y-m-d'),
            "media_path" => $this->media_path ? asset('storage/' . $this->media_path) : null,
            'adable_type' => $this->adable_type,
            'adable_id' => (int) $this->adable_id,
        ];
    }
}
