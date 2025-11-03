<?php

namespace Modules\PosTerminal\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\PosTerminalTypeEnum;
use Illuminate\Http\Request;
use App\Enums\PosTerminalStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class PosTerminalResource extends JsonResource
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
            "identifier" => $this->identifier,
            "name"       => $this->name,
            "is_active"  => (bool) $this->is_active,
           "store_name" => $this->store->name,
           'store_address' => $this->store->address_place,
           'last_sync' => $this->last_sync,
           'count_today_orders' => $this->count_today_orders,
           'amount_today_orders' => $this->amount_today_orders
        ];
    }
}
