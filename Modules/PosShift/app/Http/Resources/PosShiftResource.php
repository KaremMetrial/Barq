<?php

namespace Modules\PosShift\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\PosShiftTypeEnum;
use Illuminate\Http\Request;
use App\Enums\PosShiftStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class PosShiftResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"         => $this->id,
            "opened_at"  => $this->opened_at,
            "closed_at"  => $this->closed_at,
            "opening_balance" => $this->opening_balance,
            "closing_balance" => $this->closing_balance,
            "total_sales" => $this->total_sales,
            "pos_teminal" => $this->whenLoaded("posTerminal", function () {
                return [
                    "id" => $this->posTerminal->id,
                    "identifier" => $this->posTerminal->identifier,
                    "name" => $this->posTerminal->name,
                ];
            }),
            "vendor" => $this->whenLoaded("vendor", function () {
                return [
                    "id" => $this->vendor->id,
                    "name" => $this->vendor->first_name . ' ' . $this->vendor->last_name,
                ];
            })
        ];
    }
}
