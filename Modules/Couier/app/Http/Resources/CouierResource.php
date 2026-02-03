<?php

namespace Modules\Couier\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\CouierTypeEnum;
use App\Enums\UserStatusEnum;
use App\Enums\CouierAvaliableStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Vehicle\Http\Resources\VehicleResource;

class CouierResource extends JsonResource
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
            "first_name" => $this->first_name,
            "last_name" => $this->last_name,
            "name" => $this->first_name . " " . $this->last_name,
            "email" => $this->email,
            "phone" => $this->phone,
            "phone_code" => $this->phone_code,
            "avatar" => $this->avatar ? asset('storage/' . $this->avatar) : null,
            "license_number" => $this->license_number,
            "avaliable_status" => $this->avaliable_status?->value,
            "avaliable_status_label" => CouierAvaliableStatusEnum::label($this->avaliable_status?->value),
            "avg_rate" => $this->avg_rate,
            "status" => $this->status?->value,
            "status_label" => UserStatusEnum::label($this->status?->value),
            "store" => $this->whenLoaded('store', function () {
                return [
                    'id' => $this->store->id,
                    'name' => $this->store->name,
                ];
            }),
            "total_order" => $this->total_order ?? 0,
            "total_earning" => $this->total_earning ?? 0,
            "vehicle" => new VehicleResource($this->whenLoaded('vehicle')),
            'currency_factor' => $this->store->getCurrencyFactor(),
            'currency_code' => $this->store->getCurrencyCode(),
        ];
    }
}
