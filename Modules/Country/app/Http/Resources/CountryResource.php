<?php

namespace Modules\Country\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            "id"=> $this->id,
            "name"=> $this->name,
            "code"=> $this->code,
            "currency_symbol"=> $this->currency_symbol,
            "is_active"=> (bool) $this->is_active,
            'currency_name' => $this->currency_name,
            'flag' => $this->flag ? asset('storage/' . $this->flag) : null,
            'currency_unit' => $this->currency_unit,
            'currency_factor' => (int) $this->currency_factor,
        ];
    }
}
