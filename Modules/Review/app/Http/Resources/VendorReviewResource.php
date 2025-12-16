<?php

namespace Modules\Review\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorReviewResource extends JsonResource
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
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'formatted_date' => $this->created_at?->translatedFormat('d M Y, h:i A'),
            'has_response' => !is_null($this->response),
            'response_status' => !is_null($this->response) ? 'replied' : 'not_replied',
            'user' => [
                'name' => $this->user?->first_name . ' ' . $this->user?->last_name ?? 'Unknown',
                'avatar' => $this->user?->avatar ? asset('storage/' . $this->user->avatar) : null,
                'initials' => $this->getUserInitials()
            ],
            'order' => [
                'id' => $this->order_id,
                'order_number' => $this->order?->order_number ?? 'N/A'
            ],
            'ratings' => [
                'food_quality' => $this->food_quality_rating,
                'delivery_speed' => $this->delivery_speed_rating,
                'order_execution_speed' => $this->order_execution_speed_rating,
                'product_quality' => $this->product_quality_rating,
                'shopping_experience' => $this->shopping_experience_rating,
                'overall_experience' => $this->overall_experience_rating,
                'delivery_driver' => $this->delivery_driver_rating,
                'delivery_condition' => $this->delivery_condition_rating,
                'match_price' => $this->match_price_rating,
            ],
            'image' => $this->image
        ];
    }

    /**
     * Get user initials for avatar fallback
     */
    protected function getUserInitials(): string
    {
        if (!$this->user?->name) {
            return 'U';
        }

        $names = explode(' ', $this->user->name);
        $initials = '';

        if (count($names) > 0) {
            $initials .= strtoupper(substr($names[0], 0, 1));
        }

        if (count($names) > 1) {
            $initials .= strtoupper(substr($names[count($names) - 1], 0, 1));
        }

        return $initials;
    }
}
