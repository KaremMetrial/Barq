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

        $taxRate = $store ? $store->getTaxAmount() : 0;
        $serviceFeePercentage = $store ? $store->getServiceFeePercentage() : 0;

        // Calculate actual amounts like in OrderResource
        $taxAmount = $subtotal * ($taxRate / 100);
        $serviceFeeAmount = $subtotal * ($serviceFeePercentage / 100);


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
                // 'subtotal' => (int) \App\Helpers\CurrencyHelper::formatPrice($subtotal, $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP', $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'),
                // 'delivery_fee' => (int) \App\Helpers\CurrencyHelper::formatPrice($deliveryFee, $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP', $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'),
                // 'tax' => (int) \App\Helpers\CurrencyHelper::formatPrice($taxAmount, $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP', $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'),
                // 'service_fee' => (int) \App\Helpers\CurrencyHelper::formatPrice($serviceFeeAmount, $store->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP', $store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP'),
                // 'symbol_currency' => $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? $store ? null : null : null : null : null : null : null : null : null : null : null : null : null : null : null,
                'subtotal' => (int) $subtotal,
                'delivery_fee' => (int) $deliveryFee,
                'tax' => (int) $taxAmount,
                'service_fee' => (int) $serviceFeeAmount,
                'symbol_currency' => $store?->currency_code ?? $store?->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP',
                'currency_factor' => $store?->currency_factor ?? $store?->address?->zone?->city?->governorate?->country?->currency_factor
            ],
            'user_status' => auth('sanctum')->check() ? auth('sanctum')->user()->status->value : null,
        ];
    }
}
