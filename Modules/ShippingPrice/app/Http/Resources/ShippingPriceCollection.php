<?php

namespace Modules\ShippingPrice\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Http\Resources\PaginationResource;

class ShippingPriceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(function ($zone) {
                return new ShippingPriceCollectionResource($zone->shippingPrices);
            }),
            'pagination' => new PaginationResource($this->resource),
        ];
    }
}
