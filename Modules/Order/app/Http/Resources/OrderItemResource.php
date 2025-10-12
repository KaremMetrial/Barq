<?php
namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'total_price' => (float) $this->total_price,
            'unit_price' => (float) ($this->total_price / $this->quantity),

            'product' => $this->when($this->relationLoaded('product'), function() {
                return [
                    'id' => $this->product->id,
                    'name' => $this->product->translations->first()?->name ?? 'N/A',
                    'description' => $this->product->translations->first()?->description,
                    'image' => $this->product->images->first()?->image_path,
                    'price' => (float) ($this->product->price?->price ?? 0),
                ];
            }),

            'option' => $this->when($this->relationLoaded('productOptionValue'), function() {
                if (!$this->productOptionValue) {
                    return null;
                }

                return [
                    'id' => $this->productOptionValue->id,
                    'name' => $this->productOptionValue->productValue?->translations->first()?->name ?? 'N/A',
                    'price' => (float) $this->productOptionValue->price,
                ];
            }),

            'add_ons' => $this->when($this->relationLoaded('addOns'), function() {
                return $this->addOns->map(function($addOn) {
                    return [
                        'id' => $addOn->id,
                        'name' => $addOn->translations->first()?->name ?? 'N/A',
                        'quantity' => $addOn->pivot->quantity,
                        'price_modifier' => (float) $addOn->pivot->price_modifier,
                        'unit_price' => (float) ($addOn->pivot->price_modifier / $addOn->pivot->quantity),
                    ];
                });
            }),
        ];
    }
}

