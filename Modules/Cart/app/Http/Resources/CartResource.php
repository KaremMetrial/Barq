<?php

namespace Modules\Cart\Http\Resources;

use App\Enums\CartTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
           "cart_quantity" => $this->items->sum('quantity'),
            "cart_key" => $this->cart_key,
            "pos_shift" => $this->whenLoaded('posShift', function () {
                return $this->posShift ? [
                    "id" => $this->posShift->id,
                ] : null;
            }),
            "store" => $this->whenLoaded('store', function () {
                return $this->store ? [
                    "id" => $this->store->id,
                    "name" => $this->store->name,
                ] : null;
            }),
            "user" => $this->whenLoaded('user', function () {
                return $this->user ? [
                    "id" => $this->user->id,
                    "name" => $this->user->name,
                ] : null;
            }),

            "participants" => $this->whenLoaded('participants', function () {
                return $this->participants->map(function ($participant) {
                    return [
                        "id" => $participant->id,
                        "name" => $participant->first_name . ' ' . $participant->last_name,
                    ];
                })->toArray();
            }, []),
            "items" => CartItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
