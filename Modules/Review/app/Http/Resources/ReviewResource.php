<?php

namespace Modules\Review\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating' => $this->averageRating(),
            'comment' => $this->comment,
            'image' => $this->image ? asset('storage/' . $this->image) : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'order_id' => $this->order_id,
            'ratings' => $this->reviewRatings->map(fn($r) => [
                'rating_key' => $r->ratingKey->key,
                'value' => $r->rating,
                'label' => $r->ratingKey->label
            ])->toArray(),
            'user' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->user->id,
                    'name' => $this->order->user->first_name . ' ' . $this->order->user->last_name,
                    'avatar' => $this->order->user->avatar ? asset('storage/' . $this->user->avatar) : null,
                    'phone' => $this->order->user->phone,
                ];
            }),
            'store' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->store_id,
                    'name' => $this->order->store->name,
                    'logo' => $this->order->store->logo ? asset('storage/' . $this->order->store->logo) : null,
                    "categories" => $this->order->store->getCategoriesString(),
                ];
            }),
        ];
    }
}
