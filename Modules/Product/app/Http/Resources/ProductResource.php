<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use App\Enums\ProductStatusEnum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Http\Resources\TagResource;
use Modules\Unit\Http\Resources\UnitResource;
use Modules\AddOn\Http\Resources\AddOnResource;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Product\Traits\DeliveryTimeTrait;

class ProductResource extends JsonResource
{
    use DeliveryTimeTrait;
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "description" => $this->description,
            "is_active" => (bool) $this->is_active,
            "max_cart_quantity" => (int) $this->max_cart_quantity,
            "status" => $this->status?->value,
            "status_label" => ProductStatusEnum::label($this->status->value),
            "note" => $this->note,
            "is_reviewed" => (bool) $this->is_reviewed,
            "is_vegetarian" => (bool) $this->is_vegetarian,
            "is_featured" => (bool) $this->is_featured,
            "is_favorite" => (bool) User::isFavorite($this->id, 'product'),
            "avg_rates" => (float) $this->avg_rate,
            "barcode" => $this->barcode,
            "images" => ProductImageResource::collection($this->whenLoaded("images")),
            // "price" => $this->whenLoaded('price', function () {
            //     $price = (float) $this->price->price;
            //     $currencyCode = $this->price->currency_code ?? ($this->store?->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP');
            //     $currencySymbol = $this->price->currency_symbol ?? ($this->store?->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'ج.م');
            //     return \App\Helpers\CurrencyHelper::formatPrice($price, $currencyCode, $currencySymbol);
            // }),
            "price" => $this->whenLoaded('price', function () {
                return (int) $this->price->price;
            }),
            "currency_factor" => $this->whenLoaded('price', function () {
                return $this->price->getCurrencyFactor();
            }),
            "store"      => $this->whenLoaded('store', function () {
                $deliveryTypeUnit = $this->store->storeSetting?->delivery_type_unit ?? \App\Enums\DeliveryTypeUnitEnum::MINUTE;

                // Get user location from headers
                $userLat = request()->header('lat');
                $userLng = request()->header('lng');

                // Calculate dynamic delivery time based on location, time, and store status
                $dynamicDeliveryTimes = $this->calculateDynamicDeliveryTime($this->store, $deliveryTypeUnit, $userLat, $userLng);

                return [
                    "id"   => $this->store->id,
                    "name" => $this->store->name,
                    "logo" => $this->store->logo ? asset('storage/' . $this->store->logo) : null,
                    "delivery_time_min" => $dynamicDeliveryTimes['min'],
                    "delivery_time_max" => $dynamicDeliveryTimes['max'],
                    "delivery_type_unit" => $deliveryTypeUnit->value,
                    "is_open" => $this->store->isOpenNow(),
                    "store_type" => $this->store->section->type->value,
                ];
            }),
            "category"   => $this->whenLoaded('category', function () {
                return [
                    "id" => $this->category->id,
                    "name" => $this->category->name,
                ];
            }),
            "availability" => new ProductAvailabilityResource($this->whenLoaded("availability")),
            "tags" => TagResource::collection($this->whenLoaded("tags")),
            'units' => $this->whenLoaded('units', function () {
                return $this->units->map(function ($unit) {
                    return [
                        'id' => $unit->id,
                        'name' => $unit->name,
                        'unit_value' => $unit->pivot?->unit_value,
                    ];
                });
            }),
            "productNutrition" => new ProductNutritionResource($this->whenLoaded("ProductNutrition")),
            "productAllergen" => ProductAllergenResource::collection($this->whenLoaded("productAllergen")),
            "pharmacyInfo" => PharmacyInfoResource::collection($this->whenLoaded("pharmacyInfo")),
            "watermarks" => new ProductWatermarksResource($this->whenLoaded("watermark")),
            "cart_quantity" => $this->getCartQuantity(),
            "nearest_offer" => $this->whenLoaded('offers', function () {
                $offer = $this->offers
                    ->where('is_active', true)
                    ->where('status', \App\Enums\OfferStatusEnum::ACTIVE->value)
                    ->sortBy('end_date')
                    ->first();

                // Compute using minor units when possible for safer cross-currency calculations
                $priceMinor = $this->price->price ?? $this->price->priceMinorValue() ?? 0;
                $priceFactor = $this->price->getCurrencyFactor();
                $currencyCode = $this->price->currency_code ?? ($this->store?->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP');

                if (!$offer) {
                    return [
                        'id' => null,
                        'discount_type' => null,
                        'discount_amount' => null,
                        'start_date' => null,
                        'end_date' => null,
                        'is_flash_sale' => null,
                        'is_active' => null,
                        'has_stock_limit' =>     null,
                        'stock_limit' => null,
                        'ends_in' => null,
                        'sale_price' => $this->price->sale_price ? $this->price->sale_price : null,
                        'banner_text' => null,
                    ];
                }

                // Determine discount in minor units
                if ($offer->discount_type->value === \App\Enums\SaleTypeEnum::PERCENTAGE->value) {
                    $discountPercent = $offer->discount_amount;
                    $saleMinor = (int) $priceMinor - ($priceMinor * $discountPercent / 100);
                } else {
                    $discountMinor = (int) $offer->discount_amount ;
                    $saleMinor = (int) max(0, $priceMinor - $discountMinor);
                }

                $saleDecimal = $saleMinor;

                return [
                    'id' => $offer->id,
                    'discount_type' => $offer->discount_type->value,
                    // Represent discount amount in a human-friendly way (percentage or formatted fixed amount)
                    'discount_amount' => $offer->discount_type->value === \App\Enums\SaleTypeEnum::PERCENTAGE->value ? (int) $offer->discount_amount : \App\Helpers\CurrencyHelper::fromMinorUnits($offer->discount_amount_minor ?? \App\Helpers\CurrencyHelper::toMinorUnits((float)$offer->discount_amount, (int)($offer->currency_factor ?? $priceFactor)), (int)($offer->currency_factor ?? $priceFactor), \App\Helpers\CurrencyHelper::getDecimalPlacesForCurrency($currencyCode)) ,
                    'start_date' => $offer->start_date,
                    'end_date' => $offer->end_date,
                    'is_flash_sale' => $offer->is_flash_sale,
                    'is_active' => $offer->is_active,
                    'has_stock_limit' => $offer->has_stock_limit,
                    'stock_limit' => $offer->stock_limit,
                    'ends_in' => \Carbon\Carbon::parse($offer->end_date)->diffForHumans(),
                    'sale_price' => $saleDecimal,
                    'banner_text' => $this->getBannerTextFromOffer($offer),
                ];
            }),
            "product_options" => ProductOptionResource::collection($this->whenLoaded("productOptions")),
            "has_required_options" => $this->whenLoaded('requiredOptions', fn() => $this->requiredOptions->isNotEmpty(), false),
            "add_ons" => AddOnResource::collection($this->whenLoaded("addOns")),
            'symbol_currency' => $this->whenLoaded('store', function () {
                return $this->store->address?->zone?->city?->governorate?->country?->currency_symbol ?? 'EGP';
            }),
            'preparation_time' => $this->preparation_time,
            'preparation_time_unit' => $this->preparation_time_unit,
        ];
    }
    protected function getCartQuantity(): int
    {
        $token = request()->bearerToken();

        if ($token) {
            [, $tokenHash] = explode('|', $token, 2);

            $userId = \Illuminate\Support\Facades\DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $tokenHash))
                ->value('tokenable_id');

            if ($userId) {
                $cart = \Modules\Cart\Models\Cart::with('items')
                    ->where('user_id', $userId)
                    ->first();

                if ($cart) {
                    $item = $cart->items->firstWhere('product_id', $this->id);
                    return $item?->quantity ?? 0;
                }
            }
        }

        $cartKey = request()->header('Cart-Key') ?? request('cart_key');
        if ($cartKey) {
            $cart = \Modules\Cart\Models\Cart::with('items')
                ->where('cart_key', $cartKey)
                ->first();

            if ($cart) {
                $item = $cart->items->firstWhere('product_id', $this->id);
                return $item?->quantity ?? 0;
            }
        }

        return 0;
    }

    protected function calculateSalePrice($originalPrice, $discountAmount, $discountType, $currencyCode = 'EGP')
    {
        $decimalPlaces = \App\Helpers\CurrencyHelper::getDecimalPlacesForCurrency($currencyCode);

    if ($discountType === \App\Enums\SaleTypeEnum::PERCENTAGE->value) {
        return $originalPrice - ($originalPrice * $discountAmount / 100);
    }

    if ($discountType === \App\Enums\SaleTypeEnum::FIXED->value) {
        return max($originalPrice - $discountAmount, 0);
    }

    return $originalPrice;
    }
    protected function getBannerTextFromOffer($offer): ?string
    {
        $discount = number_format($offer->discount_amount, 0);
        $type = $offer->discount_type;

        if ($type === \App\Enums\SaleTypeEnum::PERCENTAGE) {
            return __("message.store.discount_banner_percentage", ['discount' => $discount]);
        }

        if ($type === \App\Enums\SaleTypeEnum::FIXED) {
            return __("message.store.discount_banner_fixed", ['amount' => $discount]);
        }

        return null;
    }
}
