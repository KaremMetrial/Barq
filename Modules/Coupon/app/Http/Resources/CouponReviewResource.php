<?php

namespace Modules\Coupon\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CouponReviewResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'comment' => $this->comment,
            'status' => $this->status,
            'is_verified_purchase' => $this->is_verified_purchase,
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'coupon' => $this->when($this->relationLoaded('coupon'), [
                'id' => $this->coupon->id,
                'code' => $this->coupon->code,
                'name' => $this->coupon->name,
            ]),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}