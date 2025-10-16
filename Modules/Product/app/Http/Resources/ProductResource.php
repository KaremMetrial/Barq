<?php

namespace Modules\Product\Http\Resources;

use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Modules\User\Models\User;
use App\Enums\ProductStatusEnum;
use Illuminate\Support\Facades\Auth;
use Modules\Tag\Http\Resources\TagResource;
use Modules\Unit\Http\Resources\UnitResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id"         => $this->id,
            "name"       => $this->name,
            "description" => $this->description,
            "is_active"  => (bool) $this->is_active,
            "max_cart_quantity" => (int) $this->max_cart_quantity,
            "status"     => $this->status?->value,
            "status_label" => ProductStatusEnum::label($this->status->value),
            "note"       => $this->note,
            "is_reviewed" => (bool) $this->is_reviewed,
            "is_vegetarian" => (bool) $this->is_vegetarian,
            "is_featured" => (bool) $this->is_featured,
            "is_favorite" => (bool) User::isFavorite($this->id, 'product'),
            "avg_rates" => $this->avg_rate,
            "barcode" => $this->barcode,
            "images" => ProductImageResource::collection($this->whenLoaded("images")),
            "price" => $this->whenLoaded('price', function () {
                return [
                    "price" => $this->price->price,
                ];
            }),
            "store"      => $this->whenLoaded('store', function () {
                return [
                    "id"   => $this->store->id,
                    "name" => $this->store->name,
                    "logo" => $this->store->logo ? asset('storage/' . $this->store->logo) : null,
                    "delivery_time_max" => (int) $this->store->storeSetting?->delivery_time_max,
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

                if (!$offer) {
                    return null;
                }

                $price = $this->price->price ?? 0;

                return [
                    'id' => $offer->id,
                    'discount_type' => $offer->discount_type->value,
                    'discount_amount' => $offer->discount_amount,
                    'start_date' => $offer->start_date,
                    'end_date' => $offer->end_date,
                    'is_flash_sale' => $offer->is_flash_sale,
                    'has_stock_limit' => $offer->has_stock_limit,
                    'stock_limit' => $offer->stock_limit,
                    'ends_in' => \Carbon\Carbon::parse($offer->end_date)->diffForHumans(),
                    'sale_price' => $this->calculateSalePrice(
                        $price,
                        $offer->discount_amount,
                        $offer->discount_type->value
                    ),
                    'banner_text' => $this->getBannerTextFromOffer($offer),
                ];
            }),
            "product_options" => ProductOptionResource::collection($this->whenLoaded("productOptions")),
            "has_required_options" => $this->whenLoaded('requiredOptions', fn () => $this->requiredOptions->isNotEmpty(), false),
        ];
    }
    protected function getCartQuantity(): int
    {
        $token = request()->bearerToken();

        if ($token) {
            [, $tokenHash] = explode('|', $token, 2);

            $userId = \DB::table('personal_access_tokens')
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

    protected function calculateSalePrice($originalPrice, $discountAmount, $discountType)
    {
        if ($discountType === \App\Enums\SaleTypeEnum::PERCENTAGE->value) {
            return round($originalPrice - ($originalPrice * $discountAmount / 100), 2);
        }

        if ($discountType === \App\Enums\SaleTypeEnum::FIXED->value) {
            return round(max($originalPrice - $discountAmount, 0), 2);
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
