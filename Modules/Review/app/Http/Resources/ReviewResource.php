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
            'id'            => $this->id,
            'rating'        => $this->averageRating(),
            'comment'       => $this->comment,
            'image'         => $this->image ? asset('storage/' . $this->image) : null,
            'created_at'    => $this->created_at->toDateTimeString(),
            'order_id'      => $this->order_id,
            'ratings' => $this->reviewRatings->map(fn($r) => [
                'rating_key' => $r->ratingKey->key,
                'value' => $r->rating,
                'label' => $r->ratingKey->label
            ])->toArray(),
        ];
    }
}
