<?php

namespace Modules\Store\Http\Resources;

use App\Enums\SaleTypeEnum;
use App\Enums\StoreTypeEnum;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use App\Enums\OfferStatusEnum;
use App\Enums\StoreStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Section\Http\Resources\SectionResource;
use Modules\Category\Http\Resources\CategoryResource;
use Modules\StoreSetting\Http\Resources\StoreSettingResource;
use Modules\Product\Traits\DeliveryTimeTrait;

class StoreResource extends JsonResource
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
            "status" => $this->status->value,
            "status_label" => StoreStatusEnum::label($this->status->value),
            "note" => $this->note,
            "logo" => $this->logo ? asset("storage/" . $this->logo) : null,
            "cover_image" => $this->cover_image ? asset("storage/" . $this->cover_image) : null,
            "phone" => $this->phone,
            "message" => $this->message,
            "is_featured" => (bool) $this->is_featured,
            "is_active" => (bool) $this->is_active,
            "is_closed" => (bool) $this->is_closed,
            "is_favorite" => $this->relationLoaded('currentUserFavourite') && $this->currentUserFavourite !== null,
            "avg_rate" => (float) $this->avg_rate,
            "section" => new SectionResource($this->section),
            "banners" => $this->getProductBanners(),
            "categories" => $this->getCategoriesString(),
            'store_setting' => new StoreSettingResource($this->whenLoaded('storeSetting')),
            "delivery_fee" => $this->getDeliveryFee() ? (string) $this->getDeliveryFee() : null,
            "active_sale" => $this->whenLoaded('offers', function () {
                return $this->getActiveOffers();
            }),
            "banner_text" => $this->getBannerText(),
            "is_open" => $this->isOpenNow(),
            // "cart_count" => $this->getCartCount()
            // "cart_total_price" => $this->getCartTotalPrice(),
            // "cart_item_count" => $this->getCartItemCount()
        ];
    }
    private function getCartCount(): int
    {
        $cart = $this->resolveCart();

        if (!$cart) {
            return 0;
        }

        // Count items from this store in the cart
        return $cart->items()
            ->whereHas('product', function ($query) {
                $query->where('store_id', $this->id);
            })
            ->sum('quantity');
    }
    private function getCartTotalPrice(): float
    {
        $cart = $this->resolveCart();

        if (!$cart) {
            return 0.0;
        }

        // Sum total_price of all items in the cart
        return $cart->items()->sum('total_price');
    }
    private function getCartItemCount(): int
    {
        $cart = $this->resolveCart();

        if (!$cart) {
            return 0;
        }

        // Sum quantity of all items in the cart
        return $cart->items()->sum('quantity');
    }
    private function resolveCart()
    {
        $cartKey = request()->header('Cart-Key') ?? request('cart_key');
        if ($cartKey) {
            return \Modules\Cart\Models\Cart::with('items.product')
                ->where('cart_key', $cartKey)
                ->first();
        }
    }
    private function getProductBanners(): array
    {
        $banners = [];
        if ($this->storeSetting?->free_delivery_enabled) {
            $banners[] = [
                'type' => 'free_delivery',
            ];
        }

        if ($this->created_at && $this->created_at->greaterThan(now()->subDays(30))) {
            $banners[] = [
                'type' => 'new',
            ];
        } else {
            $banners[] = [
                'type' => 'regular',
            ];
        }

        return $banners;
    }
    private function getCategoriesString(): string
    {
        if (!$this->relationLoaded('categories')) {
            return '';
        }

        return $this->categories
            ->pluck('name')
            ->filter()
            ->unique()
            ->implode(', ');
    }
    private function getActiveOffers(): array
    {
        if (!$this->relationLoaded('offers')) {
            return [];
        }

        $now = now()->toDateString();

        return $this->offers
            ->where('is_active', true)
            ->where('status', OfferStatusEnum::ACTIVE)
            ->where('start_date', '<=', $now)
            // ->where(function ($query) use ($now) {
            //     $query->whereNull('end_date')
            //         ->orWhere('end_date', '>=', $now);
            // })
            ->map(function ($offer) {
                // compute fixed discount display using offer currency or store's currency factor
                $priceFactor = $this->store_setting?->currency_factor ?? $this->address?->zone?->city?->governorate?->country?->currency_factor ?? 100;
                $currencyCode = $this->store_setting?->currency_code ?? $this->address?->zone?->city?->governorate?->country?->currency_name ?? 'EGP';

                $displayDiscount = $offer->discount_type->value === \App\Enums\SaleTypeEnum::PERCENTAGE->value ? number_format($offer->discount_amount, 0) : number_format(\App\Helpers\CurrencyHelper::fromMinorUnits($offer->discount_amount_minor ?? \App\Helpers\CurrencyHelper::toMinorUnits((float)$offer->discount_amount, (int)($offer->currency_factor ?? $priceFactor)), (int)($offer->currency_factor ?? $priceFactor), \App\Helpers\CurrencyHelper::getDecimalPlacesForCurrency($currencyCode)), 0);

                return [
                    'id' => $offer->id,
                    'discount_type' => $offer->discount_type->value,
                    'discount_amount' => $displayDiscount,
                    'discount_label' => SaleTypeEnum::label($offer->discount_type->value),
                    'start_date' => $offer->start_date,
                    'end_date' => $offer->end_date,
                    'is_flash_sale' => $offer->is_flash_sale,
                    'has_stock_limit' => $offer->has_stock_limit,
                    'stock_limit' => $offer->stock_limit,
                    'days_remaining' => $offer->end_date ? now()->diffInDays($offer->end_date, false) : null,
                    'is_ending_soon' => $offer->end_date && now()->diffInDays($offer->end_date, false) <= 3,
                ];
            })
            ->values()
            ->toArray();
    }
    private function getBannerText(): ?string
    {
        if (!$this->relationLoaded('offers')) {
            return null;
        }

        $activeOffer = $this->offers
            ->where('is_active', true)
            ->where('status', OfferStatusEnum::ACTIVE)
            ->sortByDesc('discount_amount')
            ->first();

        if (!$activeOffer) {
            return null;
        }

        $discount = number_format($activeOffer->discount_amount, 0);

        $type = $activeOffer->discount_type;

        if ($type === SaleTypeEnum::PERCENTAGE) {
            return __('message.store.discount_banner_percentage', ['discount' => $discount]);
        }

        if ($type === SaleTypeEnum::FIXED) {
            return __('message.store.discount_banner_fixed', ['amount' => $discount]);
        }
        return null;
    }
}
