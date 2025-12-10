<?php

namespace Modules\Store\Http\Resources\Admin;

use App\Enums\SaleTypeEnum;
use App\Enums\StoreTypeEnum;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use App\Enums\OfferStatusEnum;
use App\Enums\OrderStatus;
use App\Enums\StoreStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\StoreSetting\Http\Resources\StoreSettingResource;
use Modules\Vendor\Http\Resources\Admin\VendorCollectionResource;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Zone\Http\Resources\ZoneResource;
use Modules\WorkingDay\Http\Resources\WorkingDayResource;

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
            "categories" => $this->getCategoriesString(),
            'store_setting' => new StoreSettingResource($this->whenLoaded('storeSetting')),
            'recent_count_order' => $this->orders()->where('status', OrderStatus::PENDING)->count(),
            'count_order' => $this->countOrders(),
            'count_branch' => $this->countBranches(),
            'owner' => new VendorCollectionResource($this->owner),
            'address' => $this->address->getFullAddressAttribute(),
            'commission_type' => $this->commission_type->value,
            'commission_amount' => $this->commission_amount,
            'active_status' => $this->active_status,
            'branch_type' => $this->branch_type,
            "address_data" => new AddressResource($this->address),
            "zone_to_cover" => ZoneResource::collection($this->whenLoaded('zoneToCover')),
            "working_days" => WorkingDayResource::collection($this->whenLoaded('workingDays')),
            'parent' => new StoreResource($this->whenLoaded('parent')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
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
    private function getCategoriesString(): string
    {
        return $this->products->load('category.translations')
            ->pluck('category.translations.*.name')
            ->flatten()
            ->filter()
            ->unique()
            ->implode(', ');
    }
}
