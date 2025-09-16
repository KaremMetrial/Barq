<?php

namespace Modules\Language\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LanguageResource extends JsonResource
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
            "code"=> $this->code,
            "native_name" => $this->native_name,
            "direction" => $this->direction,
            "is_default" => (bool) $this->is_default,
            "is_active" => (bool) $this->is_active
        ];
    }
}
