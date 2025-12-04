<?php

namespace Modules\Reward\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RewardRedemptionResource extends JsonResource
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
            'points_spent' => (int) $this->points_spent,
            'reward_value_received' => (string) $this->reward_value_received,
            'coupon_code' => $this->coupon_code,
            'status' => $this->status,
            'redeemed_at' => $this->redeemed_at?->format('Y-m-d H:i:s'),
            'expires_at' => $this->expires_at?->format('Y-m-d H:i:s'),
            'reward' => new RewardResource($this->whenLoaded('reward')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
