<?php

namespace Modules\CompaignParicipation\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\CompaignParicipationStatusEnum;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Compaign\Http\Resources\CompaignResource;

class CompaignParicipationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"           => $this->id,
            "status"       => $this->status->value,
            "status_label" => $this->status->label(),
            "notes"        => $this->notes,
            "responded_at" => $this->responded_at?->format('Y-m-d H:i:s'),

            "compaign"     => new CompaignResource($this->whenLoaded('compaign')),
            "store"        => new StoreResource($this->whenLoaded('store')),
        ];
    }
}
