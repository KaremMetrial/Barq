<?php

namespace Modules\Admin\Http\Resources;

use App\Enums\AdminTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminResource extends JsonResource
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
            "name"=> $this->first_name . " " . $this->last_name,
            "email"=> $this->email,
            "phone"=> $this->phone,
            "avatar"=> $this->avatar ? asset('storage/' . $this->avatar) : null,
            "is_active" => (bool) $this->is_active,
        ];
    }
}
