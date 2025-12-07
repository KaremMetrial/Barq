<?php

namespace Modules\LoyaltySetting\Http\Resources;

use Illuminate\Http\Request;
use App\Enums\LoyaltyTrransactionTypeEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class LoyaltyTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => LoyaltyTrransactionTypeEnum::label($this->type->value),
            'points' => $this->points,
            'points_balance_after' => $this->points_balance_after,
            'description' => $this->description,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
        ];
    }
}
