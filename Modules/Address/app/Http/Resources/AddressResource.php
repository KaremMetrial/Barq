<?php

namespace Modules\Address\Http\Resources;

use App\Enums\AddressTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
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
            "latitude"=>(string) $this->latitude,
            "longitude"=> (string) $this->longitude,
            "name"=> $this->name,
            "phone"=> $this->phone,
            "is_default"=> request()->header('address-id') == $this->id ? true : false,
            // "type" => $this->type?->value,
            // "type_label" => AddressTypeEnum::label($this->type?->value),
            "street" => $this->street,
            "house_number" => $this->house_number,
            "apartment_number" => $this->apartment_number,
            'zone' => $this->whenLoaded('zone', function () {
                return [
                    'id'=> $this->zone->id,
                    'name'=> $this->zone->name,
                ];
            }),
            'city' => $this->whenLoaded('zone', function () {
                return [
                    'id'=> $this->zone->city_id,
                    'name'=> $this->zone->city->name,
                ];
            }),
            'address_line_1' => $this->address_line_1,
        ];
    }
}
