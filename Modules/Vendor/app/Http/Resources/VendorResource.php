<?php

namespace Modules\Vendor\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\VendorTypeEnum;
use App\Enums\VendorStatusEnum;
use Modules\Store\Http\Resources\StoreResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;

class VendorResource extends JsonResource
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
            "full_name" => $this->first_name . ' ' . $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "avatar" => $this->avatar ? asset("storage/" . $this->avatar) : null,
            "is_owner" => (bool) $this->is_owner,
            "is_active" => (bool) $this->is_active,
            "store" => new StoreResource($this->store),
        ];
    }
}
