<?php

namespace Modules\Cart\Http\Resources;

use App\Enums\CartTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Address\Models\Address;
use Modules\Order\Models\Order;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cartQuantity = $this->items->sum('quantity');
        $subtotal = $this->items->sum('total_price');

        $store = $this->store;
        $storeName = $store ? $store->name : null;

        // Calculate delivery fee based on delivery address or user location if available
        $deliveryAddressId = $request->header('address-id');
        $userLat = $request->header('lat');
        $userLng = $request->header('lng');

        if ($userLat && $userLng && !$deliveryAddressId) {
            // Use user location to calculate delivery fee if no delivery address provided
            $deliveryFee = $this->getDeliveryFeeForDisplay(null, null, $userLat, $userLng);
        } else {
            $deliveryFee = $this->getDeliveryFeeForDisplay($deliveryAddressId);
        }

        $tax = $store ? $store->getTaxAmount() : 0;
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
                    'name' => $this->user->first_name . ' ' . $this->user->last_name,
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
            'price_summary' => [
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'tax' => $tax,
                'symbol_currency' => $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'
            ],
        ];
    }
}
