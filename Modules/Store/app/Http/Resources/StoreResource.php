<?php

namespace Modules\Store\Http\Resources;

use App\Enums\StoreStatusEnum;
use App\Enums\StoreTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;

class StoreResource extends JsonResource
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
            "status"=> $this->status->value,
            "status_label" => StoreStatusEnum::label($this->status->value),
            "note"=> $this->note,
            "logo" => $this->logo ? asset("storage/". $this->logo) : null,
            "cover_image" => $this->cover_image ? asset("storage/". $this->cover_image) : null,
            "phone"=> $this->phone,
            "message"=> $this->message,
            "is_featured"=> (bool) $this->is_featured,
            "is_active"=> (bool) $this->is_active,
            "is_closed"=> (bool) $this->is_closed,
            "avg_rate"=> $this->avg_rate,
            "section"=> new SectionResource($this->section),
        ];
    }
}
