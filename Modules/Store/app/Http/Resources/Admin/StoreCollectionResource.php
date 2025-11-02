<?php

namespace Modules\Store\Http\Resources\Admin;

use App\Enums\SaleTypeEnum;
use App\Enums\StoreTypeEnum;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use App\Enums\OfferStatusEnum;
use App\Enums\StoreStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\StoreSetting\Http\Resources\StoreSettingResource;

class StoreCollectionResource extends JsonResource
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
            "name" => $this->name,
            "status" => $this->status->value,
            "status_label" => StoreStatusEnum::label($this->status->value),
            "phone" => $this->phone,
            "is_featured" => (bool) $this->is_featured,
            "is_active" => (bool) $this->is_active,
            "is_closed" => (bool) $this->is_closed,
            "avg_rate" => $this->avg_rate,
            "owner_name" => $this->owner?->first_name . ' ' . $this->owner?->last_name,
            'section_name' => $this->section?->name,
            'active_status' => $this->active_status,
        ];
    }
}
