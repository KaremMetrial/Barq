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
            "total_price" => $this->total_price,

            "product" => $this->whenLoaded('product', function () {
                return new ProductResource($this->product);
            }),

            "product_option_values" => $this->when(is_array($this->product_option_value_id), function () {
                $optionIds = $this->product_option_value_id;
                if (is_array($optionIds)) {
                    $options = \Modules\Product\Models\ProductOptionValue::whereIn('id', $optionIds)->get();
                    return $options->map(function ($option) {
                        return [
                            "id" => $option->id,
                            "price" => $option->price,
                            "name" => optional($option->productValue)->name,
                        ];
                    })->toArray();
                }
                return [];
            }, []),

            "add_ons" => $this->whenLoaded('addOns', function () {

                return AddOnResource::collection($this->addOns);

            //     return $this->addOns->map(function ($addOn) {
            //         return [
            //             "id" => $addOn->id,
            //             "name" => $addOn->name,
            //             "price" => $addOn->price,
            //             "quantity" => optional($addOn->pivot)->quantity ?? 1,
            //             "price_modifier" => optional($addOn->pivot)->price_modifier ?? 0,
            //         ];
            //     })->toArray();
            }, []),

            "added_by" => $this->whenLoaded('addedBy', function () {
                return $this->addedBy ? [
                    "id" => $this->addedBy->id,
                    "name" => $this->addedBy->first_name . ' ' . $this->addedBy->last_name,
                ] : null;
            }),
        ];
    }
}
