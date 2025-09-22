<?php

namespace Modules\Balance\Http\Resources;

use App\Enums\BalanceTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
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
            "available_balance"=> $this->available_balance,
            "pending_balance"=> $this->pending_balance,
            "total_balance"=> $this->total_balance,
            "balanceable_id"=> (int) $this->balanceable_id,
            "balanceable_type"=> $this->balanceable_type,
        ];
    }
}
