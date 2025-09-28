<?php

namespace Modules\Cart\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            "id"=> $this->id,
            "quantity"=> $this->quantity,
            "note"=> $this->note,
            "total_price"=> $this->total_price,
            "product"=> $this->whenLoaded('product', function () {
                return $this->product ? [
                    "id" => $this->product->id,
                    "name" => $this->product->name,
                ] : null;
            }),
            "product_option_value"=> $this->whenLoaded('productOptionValue', function () {
                return $this->productOptionValue ? [
                    "id" => $this->productOptionValue->id,
                    "price" => $this->productOptionValue->price,
                    "name" => $this->productOptionValue->productValue->name,
                ] : null;
            }),
            // "add_ons"=> $this->whenLoaded('addOns', function () {
            //     return $this->addOns->map(function ($addOn) {
            //         return [
            //             "id" => $addOn->id,
            //             "name" => $addOn->name,
            //             "price" => $addOn->price,
            //             "pivot" => [
            //                 "quantity" => $addOn->pivot->quantity,
            //                 "price" => $addOn->pivot->price,
            //             ],
            //         ];
            //     });
            // }),
        ];
    }
}
