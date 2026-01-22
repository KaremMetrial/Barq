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

class DeliveryCompanyResource extends JsonResource
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
            "phone" => $this->phone,
            "is_featured" => (bool) $this->is_featured,
            "is_active" => (bool) $this->is_active,
            "is_closed" => (bool) $this->is_closed,
            "avg_rate" => $this->avg_rate,
            "section" => new SectionResource($this->section),
            "total_couriers" => $this->couriers_count,
            "total_orders" => $this->orders_count,
            "success_rate" => $this->orders_count > 0 ? round(($this->successful_orders_count / $this->orders_count) * 100, 2) : 0,
            "total_revenue" => "15000",
            'symbol_currency' => $this->getCurrencyCode(),
            'currency_factor' => $this->getCurrencyFactor(),
        ];
    }

}
