<?php

namespace Modules\Banner\Http\Resources;

use App\Enums\BannerTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
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
            "title"=> $this->title,
            "image"=> $this->image ? asset('storage/' . $this->image) : null,
            "start_date"=> $this->start_date?->format('Y-m-d H:i:s'),
            "end_date"=> $this->end_date?->format('Y-m-d H:i:s'),
            "link"=> $this->link,
            "is_active"=> (bool) $this->is_active,
            "city"=> $this->whenLoaded('city', function () {
                return [
                    'id'=> $this->city->id,
                    'name'=> $this->city->name,
                ];
            }),
        ];
    }
}
