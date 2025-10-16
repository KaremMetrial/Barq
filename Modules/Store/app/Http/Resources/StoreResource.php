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

class StoreResource extends JsonResource
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
            "avg_rate" => $this->avg_rate,
            "section" => new SectionResource($this->section),
            "banners" => $this->getProductBanners(),
            "categories" => $this->getCategoriesString(),
            'store_setting' => new StoreSettingResource($this->whenLoaded('storeSetting')),
            "delivery_fee" => $this->getDeliveryFee(),
            "active_sale" => $this->whenLoaded('offers', function () {
                return $this->getActiveOffers();
            }),
            "banner_text" => $this->getBannerText(),
            // "cart_count" => $this->getCartCount()
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
    private function resolveCart()
    {
        $token = request()->bearerToken();

        // Try to get cart by user token
        if ($token) {
            [, $tokenHash] = explode('|', $token, 2);

            $userId = \DB::table('personal_access_tokens')
                ->where('token', hash('sha256', $tokenHash))
                ->value('tokenable_id');

            if ($userId) {
                return \Modules\Cart\Models\Cart::with('items.product')
                    ->where('user_id', $userId)
                    ->first();
            }
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
        if (!$this->relationLoaded('section') || !$this->section->relationLoaded('categories')) {
            return '';
        }

        return $this->section->categories
            ->pluck('translations.*.name')
            ->flatten()
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
                // dd($offer);
                return [
                    'id' => $offer->id,
                    'discount_type' => $offer->discount_type->value,
                    'discount_amount' => $offer->discount_amount,
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
