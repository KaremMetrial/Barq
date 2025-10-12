<?php

namespace Modules\Store\Http\Resources;

use App\Enums\StoreTypeEnum;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use App\Enums\StoreStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\StoreSetting\Http\Resources\StoreSettingResource;

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
            "id" => $this->id,
            "name" => $this->name,
            "status" => $this->status->value,
            "status_label" => StoreStatusEnum::label($this->status->value),
            "note" => $this->note,
            "logo" => $this->logo ? asset("storage/" . $this->logo) : null,
            "cover_image" => $this->cover_image ? asset("storage/" . $this->cover_image) : null,
            "phone" => $this->phone,
            "message" => $this->message,
            "is_featured" => (bool) $this->is_featured,
            "is_active" => (bool) $this->is_active,
            "is_closed" => (bool) $this->is_closed,
            "is_favorite" => $this->relationLoaded('currentUserFavourite') && $this->currentUserFavourite !== null,
            "avg_rate" => $this->avg_rate,
            "section" => new SectionResource($this->section),
            "banners" => $this->getProductBanners(),
            "categories" => CategoryResource::collection($this->whenLoaded('section')->categories),
            'store_setting' => new StoreSettingResource($this->whenLoaded('storeSetting')),
            "delivery_fee" => $this->getDeliveryFee(),
        ];
    }
    private function getProductBanners(): array
    {
        $banners = [];
        if ($this->storeSetting?->free_delivery_enabled) {
            $banners[] = [
                'type' => 'free_delivery',
            ];
        }

        if ($this->created_at && $this->created_at->greaterThan(now()->subDays(30))) {
            $banners[] = [
                'type' => 'new',
            ];
        } else {
            $banners[] = [
                'type' => 'regular',
            ];
        }

        return $banners;
    }
}
