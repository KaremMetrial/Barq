<?php

namespace Modules\Tag\Http\Resources;

use App\Enums\TagTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
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
            "is_active" => (bool) $this->is_active,
        ];
    }
}
