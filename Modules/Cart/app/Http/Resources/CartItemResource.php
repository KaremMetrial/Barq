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

            "product_option_value" => $this->whenLoaded('productOptionValue', function () {
                return new ProductOptionValueResource($this->productOptionValue);

                // return $this->productOptionValue ? [
                //     "id" => $this->productOptionValue->id,
                //     "price" => $this->productOptionValue->price,
                //     "name" => optional($this->productOptionValue->productValue)->name,
                // ] : null;
            }),

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
