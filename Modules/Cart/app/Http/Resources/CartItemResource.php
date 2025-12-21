<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\AddOn\Http\Resources\AddOnResource;
use Modules\Product\Http\Resources\ProductResource;
use Modules\Product\Http\Resources\ProductOptionValueResource;

class CartItemResource extends JsonResource
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
            "quantity" => $this->quantity,
            "note" => $this->note,
            "total_price" => (int) $this->total_price,
            "selected_options" => $this->getSelectedOptionsString(),

            "product" => $this->whenLoaded('product', function () {
                return new ProductResource($this->product->load('store', 'translations', 'images', 'price', 'offers'));
            }),

            "options" => $this->when(is_array($this->product_option_value_id), function () {
                $optionIds = $this->product_option_value_id;
                if (is_array($optionIds)) {
                    $options = \Modules\Product\Models\ProductOptionValue::whereIn('id', $optionIds)->get();
                    return $options->map(function ($option) {
                        return [
                            "id" => $option->id,
                            "price" => (int) $option->price,
                            "name" => optional($option->productValue)->name,
                        ];
                    })->toArray();
                }
                return [];
            }, []),

            "add_ons" => $this->whenLoaded('addOns', function () {
                return AddOnResource::collection($this->addOns);
            }, []),

            "added_by" => $this->whenLoaded('addedBy', function () {
                return $this->addedBy ? [
                    "id" => $this->addedBy->id,
                    "name" => $this->addedBy->first_name . ' ' . $this->addedBy->last_name,
                ] : null;
            }),
        ];
    }
    private function getSelectedOptionsString(): string
    {
        $parts = [];

        // Add Options
        if (is_array($this->product_option_value_id)) {
            $options = \Modules\Product\Models\ProductOptionValue::whereIn('id', $this->product_option_value_id)
                ->with('productValue.translations')
                ->get();

            foreach ($options as $option) {
                if ($name = $option->productValue?->name) {
                    $parts[] = $name;
                }
            }
        }

        // Add Add-ons
        if ($this->relationLoaded('addOns')) {
            foreach ($this->addOns as $addOn) {
                if ($name = $addOn?->name) {
                    $parts[] = $name;
                }
            }
        }

        return implode(', ', $parts);
    }
}
